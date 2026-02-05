import subprocess
import json
import os
import re

# Configurations
STORAGE_PATH = "myrvm-server/storage/app/private/dataset/images/raw"
IDENTIFY_BIN = "/Applications/ServBay/bin/identify"
DOCKER_CONTAINER = "myrvm-app"

def run_command(cmd, cwd=None):
    result = subprocess.run(cmd, shell=True, capture_output=True, text=True, cwd=cwd)
    return result.stdout.strip(), result.stderr.strip()

def get_db_records():
    # Fetch all filenames and their paths from DB
    tinker_cmd = "docker exec " + DOCKER_CONTAINER + " php artisan tinker --execute=\"echo json_encode(\\App\\Models\\DatasetImageRaw::select('filename', 'file_path', 'captured_at')->get());\""
    stdout, stderr = run_command(tinker_cmd)
    try:
        # Extract JSON from tinker output (might have warnings before)
        json_match = re.search(r'\[.*\]', stdout, re.DOTALL)
        if json_match:
            return json.loads(json_match.group())
        else:
            print(f"Failed to parse JSON from tinker: {stdout}")
            return []
    except Exception as e:
        print(f"Error fetching DB records: {e}")
        return []

def check_color_profile(file_path):
    cmd = f"{IDENTIFY_BIN} -verbose {file_path} | grep -i 'Profile-icc' -A 5"
    stdout, stderr = run_command(cmd)
    if "sRGB" in stdout or "srgb" in stdout:
        return True, "sRGB"
    elif "Profile-icc" in stdout:
        return True, "ICC Profile Found (Other)"
    else:
        return False, "No ICC Profile"

def main():
    print("--- Starting Dataset Verification ---")
    
    # 1. Get Disk Files
    disk_files = os.listdir(STORAGE_PATH)
    disk_files = [f for f in disk_files if f.endswith(('.jpg', '.jpeg', '.png'))]
    print(f"Files on disk: {len(disk_files)}")
    
    # 2. Get DB Records
    db_records = get_db_records()
    print(f"Records in DB: {len(db_records)}")
    
    db_filenames = [r['filename'] for r in db_records]
    
    results = []
    
    # 3. Cross-Check
    all_filenames = set(disk_files) | set(db_filenames)
    
    for filename in sorted(all_filenames):
        in_disk = filename in disk_files
        in_db = filename in db_filenames
        
        status = "OK"
        profile_status = "N/A"
        
        if in_disk and not in_db:
            status = "ORPHAN (Disk only)"
        elif in_db and not in_disk:
            status = "MISSING (DB only)"
        
        if in_disk:
            file_path = os.path.join(STORAGE_PATH, filename)
            has_profile, profile_desc = check_color_profile(file_path)
            profile_status = profile_desc
            if not has_profile:
                status = "WARNING (No sRGB)" if status == "OK" else status + " + No sRGB"

        results.append({
            "filename": filename,
            "in_disk": in_disk,
            "in_db": in_db,
            "status": status,
            "profile": profile_status
        })

    # 4. Report
    print("\nVerification Results:")
    print(f"{'Filename':<40} | {'Disk':<5} | {'DB':<5} | {'Profile':<20} | {'Status'}")
    print("-" * 100)
    for res in results:
        print(f"{res['filename']:<40} | {str(res['in_disk']):<5} | {str(res['in_db']):<5} | {res['profile']:<20} | {res['status']}")

    # Summary
    ok_count = sum(1 for r in results if r['status'] == "OK")
    warning_count = sum(1 for r in results if "WARNING" in r['status'])
    missing_count = sum(1 for r in results if "MISSING" in r['status'])
    orphan_count = sum(1 for r in results if "ORPHAN" in r['status'])
    
    print("\n--- Summary ---")
    print(f"Total Unique Items: {len(all_filenames)}")
    print(f"Healthy (OK): {ok_count}")
    print(f"Warnings (No sRGB): {warning_count}")
    print(f"Missing Files: {missing_count}")
    print(f"Orphan Files: {orphan_count}")

if __name__ == "__main__":
    main()
