UPDATE oc_b2sharebridge_filecache_status SET status=0 WHERE status='published';
UPDATE oc_b2sharebridge_filecache_status SET status=1 WHERE status='new';
UPDATE oc_b2sharebridge_filecache_status SET status=2 WHERE status='processing';
UPDATE oc_b2sharebridge_filecache_status SET status=3 WHERE status='External error: during uploading file';
UPDATE oc_b2sharebridge_filecache_status SET status=4 WHERE status='External error: during creating deposit';
UPDATE oc_b2sharebridge_filecache_status SET status=5 WHERE status='Internal error: file not accessible';
