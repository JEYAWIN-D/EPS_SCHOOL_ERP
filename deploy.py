import os
import sys
import subprocess
import paramiko

# Ensure standard UTF-8 output on Windows consoles
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

VPS_HOST = "187.127.150.113"
VPS_PORT = 22
VPS_USER = "root"
VPS_PASS = "Kauq0E;v9h;K3TYZ"
REMOTE_PATH = "/var/www/dasa-eps-school"
LOCAL_PATH = os.path.dirname(os.path.abspath(__file__))

IGNORED_PATHS = {
    ".env",
    ".git",
    ".agents",
    ".gemini",
    ".vscode",
    "node_modules",
    "vendor",
    "storage",
    "deploy.py",
    "deploy.bat",
}

def get_modified_files():
    """Detect modified and newly added files using git status."""
    files_to_sync = set()
    try:
        result = subprocess.run(
            ["git", "status", "--porcelain"],
            cwd=LOCAL_PATH,
            capture_output=True,
            text=True,
            check=True
        )
        for line in result.stdout.strip().splitlines():
            if not line:
                continue
            status = line[:2]
            filepath = line[3:].strip().replace('"', '')
            # Ignore deleted files and ignored folders
            parts = filepath.replace('\\', '/').split('/')
            if parts[0] in IGNORED_PATHS or filepath in IGNORED_PATHS:
                continue
            if not status.startswith(" D"):
                full_local = os.path.join(LOCAL_PATH, filepath)
                if os.path.isfile(full_local):
                    files_to_sync.add(filepath.replace("\\", "/"))
    except Exception as e:
        print(f"[!] Warning checking git: {e}")

    return sorted(list(files_to_sync))

def deploy():
    print("=" * 60)
    print("  [*] DEPLOYING TO HOSTINGER LIVE SERVER (eps.dasatech.in)")
    print("=" * 60)

    # 1. Determine files to upload
    files = get_modified_files()

    if not files:
        print("[i] No changed files detected via git status.")
        print("[*] Server is already up to date with your local code!")
        return

    print(f"[*] Found {len(files)} modified file(s) to upload:")
    for f in files[:10]:
        print(f"    - {f}")
    if len(files) > 10:
        print(f"    ... and {len(files) - 10} more files.")

    # 2. Connect via SSH / SFTP
    print(f"\n[*] Connecting to {VPS_HOST} via SSH...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    try:
        ssh.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, password=VPS_PASS, timeout=15)
        sftp = ssh.open_sftp()
        print("[+] Connected successfully!")

        # 3. Upload files
        print("[*] Uploading files to server...")
        uploaded = 0
        for rel_path in files:
            local_file = os.path.join(LOCAL_PATH, rel_path)
            remote_file = f"{REMOTE_PATH}/{rel_path}"
            remote_dir = os.path.dirname(remote_file).replace('\\', '/')

            # Ensure remote directory exists
            dirs_to_create = []
            cur = remote_dir
            while cur and cur != REMOTE_PATH and cur != "/":
                dirs_to_create.append(cur)
                cur = os.path.dirname(cur).replace('\\', '/')
            for d in reversed(dirs_to_create):
                try:
                    sftp.stat(d)
                except IOError:
                    try:
                        sftp.mkdir(d)
                    except Exception:
                        pass

            sftp.put(local_file, remote_file)
            uploaded += 1
            print(f"    [+] Uploaded: {rel_path}")

        sftp.close()
        print(f"\n[+] Successfully uploaded {uploaded} file(s)!")

        # 4. Run post-deploy commands
        print("[*] Clearing cache and reloading live server...")
        remote_cmd = (
            f"cd {REMOTE_PATH} && "
            f"chown -R www-data:www-data {REMOTE_PATH} && "
            f"chmod -R 775 storage bootstrap/cache && "
            f"php artisan optimize:clear && "
            f"systemctl reload php8.5-fpm"
        )
        stdin, stdout, stderr = ssh.exec_command(remote_cmd)
        out = stdout.read().decode().strip()

        if out:
            for line in out.splitlines():
                if "cleared" in line.lower() or "info" in line.lower():
                    print(f"    {line.strip()}")

        ssh.close()

        print("\n" + "=" * 60)
        print("  [SUCCESS] DEPLOYMENT COMPLETE!")
        print("  Live Website: https://eps.dasatech.in")
        print("=" * 60 + "\n")

    except Exception as e:
        print(f"\n[!] Deployment failed: {e}")
        if 'ssh' in locals():
            ssh.close()

if __name__ == "__main__":
    deploy()
