import boto3
import yaml
import sys

def load_config(path="bucket-config.yml"):
    with open(path, "r") as f:
        return yaml.safe_load(f)

def check_public_access(s3, bucket_name):
    try:
        response = s3.get_public_access_block(Bucket=bucket_name)
        config = response["PublicAccessBlockConfiguration"]
        return all([
            config.get("BlockPublicAcls", False),
            config.get("IgnorePublicAcls", False),
            config.get("BlockPublicPolicy", False),
            config.get("RestrictPublicBuckets", False),
        ])
    except s3.exceptions.NoSuchPublicAccessBlockConfiguration:
        return False

def check_encryption(s3, bucket_name):
    try:
        s3.get_bucket_encryption(Bucket=bucket_name)
        return True
    except s3.exceptions.ClientError as e:
        if e.response["Error"]["Code"] == "ServerSideEncryptionConfigurationNotFoundError":
            return False
        raise

def check_versioning(s3, bucket_name):
    response = s3.get_bucket_versioning(Bucket=bucket_name)
    return response.get("Status") == "Enabled"

def check_bucket(s3, bucket):
    name = bucket["name"]
    failures = []

    print(f"\n🔍 Checking bucket: {name}")

    # Public access check
    actual_public_blocked = check_public_access(s3, name)
    expected_public_blocked = bucket.get("public_access_blocked", True)
    if actual_public_blocked != expected_public_blocked:
        failures.append(
            f"  ❌ Public access blocked: expected {expected_public_blocked}, got {actual_public_blocked}"
        )
    else:
        print(f"  ✅ Public access blocked: {actual_public_blocked}")

    # Encryption check
    actual_encryption = check_encryption(s3, name)
    expected_encryption = bucket.get("encryption", True)
    if actual_encryption != expected_encryption:
        failures.append(
            f"  ❌ Encryption: expected {expected_encryption}, got {actual_encryption}"
        )
    else:
        print(f"  ✅ Encryption enabled: {actual_encryption}")

    # Versioning check
    actual_versioning = check_versioning(s3, name)
    expected_versioning = bucket.get("versioning", True)
    if actual_versioning != expected_versioning:
        failures.append(
            f"  ❌ Versioning: expected {expected_versioning}, got {actual_versioning}"
        )
    else:
        print(f"  ✅ Versioning enabled: {actual_versioning}")

    return failures

def main():
    config = load_config()
    s3 = boto3.client("s3", region_name="eu-west-2")

    all_failures = {}

    for bucket in config["buckets"]:
        failures = check_bucket(s3, bucket)
        if failures:
            all_failures[bucket["name"]] = failures

    print("\n" + "="*50)

    if all_failures:
        print("\n❌ COMPLIANCE CHECK FAILED\n")
        for bucket_name, failures in all_failures.items():
            print(f"Bucket: {bucket_name}")
            for failure in failures:
                print(failure)
        print()
        sys.exit(1)
    else:
        print("\n✅ ALL BUCKETS COMPLIANT\n")
        sys.exit(0)

if __name__ == "__main__":
    main()