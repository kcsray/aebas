CREATE DEFINER=`root`@`localhost` PROCEDURE `IssueDevice`(
    IN p_empcode VARCHAR(8),
    IN p_slno VARCHAR(20),
    IN p_Loc_ID VARCHAR(10),
    IN p_issue_date DATE
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK; -- Rollback on error
        RESIGNAL; -- Propagate the error
    END;

    START TRANSACTION; -- Start transaction

    -- Insert into 'issued' table
    INSERT INTO issued (emp_cd, device_slno, Loc_ID,issu_date)
    VALUES (p_empcode, p_slno,p_Loc_ID, p_issue_date);

    -- Update status in 'devices' table to 1 (issued)
    UPDATE device
    SET Isissued = 1
    WHERE SLNO = p_slno;

    COMMIT; -- Commit both operations
END