CREATE DEFINER=`root`@`localhost` PROCEDURE `process_device_return_with_date`(
    IN p_device_slno VARCHAR(50),
    IN p_emp_cd VARCHAR(20),
    IN p_return_date DATE
)
BEGIN
    DECLARE v_loc_id VARCHAR(20);
    DECLARE v_issu_date DATE;
    
    -- Get device details before deleting
    SELECT Loc_ID, issu_date INTO v_loc_id, v_issu_date
    FROM issued
    WHERE device_slno = p_device_slno AND emp_cd = p_emp_cd;
    
    -- Insert into returned table with provided return date
    INSERT INTO returned (emp_cd, device_slno, Loc_ID, issu_date, return_date)
    VALUES (p_emp_cd, p_device_slno, v_loc_id, v_issu_date, p_return_date);
    
    -- Update device status
    UPDATE device SET isissued = 0 WHERE SLNO = p_device_slno;
    
    -- Delete from issued table
    DELETE FROM issued WHERE device_slno = p_device_slno AND emp_cd = p_emp_cd;
    
    COMMIT;
END