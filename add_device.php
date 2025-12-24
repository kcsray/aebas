<?php
// Include your existing config file
require_once 'config.php';

// Handle AJAX search requests separately - MUST be at the very top
if(isset($_GET['ajax_search']) && isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $query = "SELECT SLNO, Type FROM Device 
              WHERE SLNO LIKE :search OR Type LIKE :search 
              ORDER BY SLNO LIMIT 10";
    
    $stmt = $pdo->prepare($query);
    $searchParam = "%" . $searchTerm . "%";
    $stmt->bindParam(":search", $searchParam);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formattedResults = array();
    foreach($results as $row) {
        $formattedResults[] = array(
            'id' => $row['SLNO'],
            'text' => $row['SLNO'] . ' (' . $row['Type'] . ')'
        );
    }
    
    header('Content-Type: application/json');
    echo json_encode($formattedResults);
    exit;
}

// Handle Add Device
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_device'])) {
    try {
        $query = "INSERT INTO Device (SLNO, Type, Isissued, dev_status) 
                  VALUES (:SLNO, :Type, :Isissued, :dev_status)";
        
        $stmt = $pdo->prepare($query);
        
        $stmt->bindParam(":SLNO", $_POST['SLNO']);
        $stmt->bindParam(":Type", $_POST['Type']);
        $isIssued = isset($_POST['Isissued']) ? 1 : 0;
        $stmt->bindParam(":Isissued", $isIssued, PDO::PARAM_INT);
        $devStatus = isset($_POST['dev_status']) ? 1 : 0;
        $stmt->bindParam(":dev_status", $devStatus, PDO::PARAM_INT);
        
        if($stmt->execute()) {
            $addSuccess = true;
        }
    } catch(PDOException $e) {
        $addError = "Error adding device: " . $e->getMessage();
    }
}

// Handle Edit Device
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_device'])) {
    try {
        $query = "UPDATE Device 
                  SET Type = :Type, Isissued = :Isissued, dev_status = :dev_status 
                  WHERE SLNO = :SLNO";
        
        $stmt = $pdo->prepare($query);
        
        $stmt->bindParam(":SLNO", $_POST['edit_SLNO']);
        $stmt->bindParam(":Type", $_POST['edit_Type']);
        $isIssued = isset($_POST['edit_Isissued']) ? 1 : 0;
        $stmt->bindParam(":Isissued", $isIssued, PDO::PARAM_INT);
        $devStatus = isset($_POST['edit_dev_status']) ? 1 : 0;
        $stmt->bindParam(":dev_status", $devStatus, PDO::PARAM_INT);
        
        if($stmt->execute()) {
            $editSuccess = true;
            // Redirect to avoid form resubmission
            header("Location: add_device.php?load_device=" . urlencode($_POST['edit_SLNO']) . "&success=1");
            exit;
        }
    } catch(PDOException $e) {
        $editError = "Error updating device: " . $e->getMessage();
    }
}

// Get device data for editing if SLNO is provided in URL
$editDeviceData = null;
if(isset($_GET['load_device'])) {
    $query = "SELECT * FROM Device WHERE SLNO = :SLNO LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":SLNO", $_GET['load_device']);
    $stmt->execute();
    $editDeviceData = $stmt->fetch();
}

// Get all devices for the table
$query = "SELECT * FROM Device ORDER BY SLNO";
$devices = $pdo->query($query)->fetchAll();

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN dev_status = 1 THEN 1 ELSE 0 END) as ok,
    SUM(CASE WHEN Isissued = 1 THEN 1 ELSE 0 END) as issued,
    SUM(CASE WHEN dev_status = 0 THEN 1 ELSE 0 END) as defective
    FROM Device";
$stats = $pdo->query($statsQuery)->fetch();

// Check for success message
if(isset($_GET['success'])) {
    $editSuccess = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Management System - Add/Edit Devices</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Select2 for searchable dropdown -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: none;
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        .status-ok {
            color: #28a745;
            font-weight: bold;
        }
        .status-defective {
            color: #dc3545;
            font-weight: bold;
        }
        .issued-yes {
            color: #17a2b8;
            font-weight: bold;
        }
        .issued-no {
            color: #6c757d;
            font-weight: bold;
        }
        .form-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .nav-tabs .nav-link {
            font-weight: 500;
        }
        .tab-content {
            background: white;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h1 class="text-primary">📱 Device Management System</h1>
                <p class="lead">Add New Device & Edit Existing Devices</p>
                <div class="d-flex justify-content-center gap-3 mt-3">
                    <a href="/aebas" class="btn btn-primary">➕ Home</a>
                    <a href="total_device_rpt.php" class="btn btn-outline-secondary">📋 View All Devices</a>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if(isset($addSuccess) && $addSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ✅ Device added successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if(isset($addError)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            ❌ <?php echo htmlspecialchars($addError); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if(isset($editSuccess) && $editSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ✅ Device updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if(isset($editError)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            ❌ <?php echo htmlspecialchars($editError); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Device List (Left Column) -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Device Inventory (<?php echo count($devices); ?> devices)</span>
                        <button class="btn btn-sm btn-light" onclick="refreshDeviceList()">
                            🔄 Refresh
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>SLNO</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Issued</th>
                                        <th>Condition</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($devices as $device): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($device['SLNO']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($device['Type']); ?></td>
                                        <td class="<?php echo $device['dev_status'] == 1 ? 'status-ok' : 'status-defective'; ?>">
                                            <?php echo $device['dev_status'] == 1 ? 'OK' : 'Defective'; ?>
                                        </td>
                                        <td class="<?php echo $device['Isissued'] == 1 ? 'issued-yes' : 'issued-no'; ?>">
                                            <?php echo $device['Isissued'] == 1 ? 'Yes' : 'No'; ?>
                                        </td>
                                        <td>
                                            <?php echo $device['dev_status'] == 1 ? '✅ OK' : '❌ Defective'; ?>
                                        </td>
                                        <td>
                                            <a href="add_device.php?load_device=<?php echo urlencode($device['SLNO']); ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                ✏️ Edit
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Device Management Forms (Right Column) -->
            <div class="col-md-4">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs" id="deviceTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo !isset($_GET['load_device']) ? 'active' : ''; ?>" 
                                id="add-tab" data-bs-toggle="tab" data-bs-target="#add" 
                                type="button" role="tab" aria-controls="add" aria-selected="true">
                            ➕ Add New
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo isset($_GET['load_device']) ? 'active' : ''; ?>" 
                                id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit" 
                                type="button" role="tab" aria-controls="edit" aria-selected="false">
                            ✏️ Edit Existing
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="deviceTabsContent">
                    <!-- Add New Device Tab -->
                    <div class="tab-pane fade <?php echo !isset($_GET['load_device']) ? 'show active' : ''; ?>" 
                         id="add" role="tabpanel" aria-labelledby="add-tab">
                        <h4 class="mb-4 text-success">Add New Device</h4>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Device SLNO *</label>
                                <input type="text" class="form-control" name="SLNO" 
                                       pattern="[A-Za-z0-9]{10}" title="10 characters alphanumeric" 
                                       maxlength="10" required>
                                <div class="form-text">Enter 10-character alphanumeric serial number</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Device Type *</label>
                                <select class="form-select" name="Type" required>
                                    <option value="">Select Type</option>
                                    <option value="L1">L1</option>
                                    <option value="L2">L2</option>
                                    <option value="M1">M1</option>
                                    <option value="M2">M2</option>
                                    <option value="T1">T1</option>
                                    <option value="T2">T2</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           name="Isissued" value="1" id="isIssued" role="switch">
                                    <label class="form-check-label" for="isIssued">
                                        Device Issued?
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           name="dev_status" value="1" id="devStatus" checked role="switch">
                                    <label class="form-check-label" for="devStatus">
                                        Device is OK
                                    </label>
                                    <div class="form-text">Turn OFF if device is defective</div>
                                </div>
                            </div>
                            
                            <button type="submit" name="add_device" class="btn btn-success w-100">
                                ➕ Add New Device
                            </button>
                        </form>
                    </div>

                    <!-- Edit Device Tab -->
                    <div class="tab-pane fade <?php echo isset($_GET['load_device']) ? 'show active' : ''; ?>" 
                         id="edit" role="tabpanel" aria-labelledby="edit-tab">
                        <h4 class="mb-4 text-primary">Edit Device</h4>
                        
                        <?php if($editDeviceData): ?>
                        <div class="alert alert-info mb-3">
                            Editing: <strong><?php echo htmlspecialchars($editDeviceData['SLNO']); ?></strong>
                            <a href="add_device.php" class="float-end btn btn-sm btn-outline-secondary">
                                ✖ Clear
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="editForm">
                            <div class="mb-3">
                                <label class="form-label">Select Device SLNO *</label>
                                <select class="form-control select2-device" name="edit_SLNO" 
                                        id="deviceSelect" required>
                                    <option value="">Search device by SLNO or Type...</option>
                                    <?php if($editDeviceData): ?>
                                    <option value="<?php echo htmlspecialchars($editDeviceData['SLNO']); ?>" selected>
                                        <?php echo htmlspecialchars($editDeviceData['SLNO'] . ' (' . $editDeviceData['Type'] . ')'); ?>
                                    </option>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text">Start typing to search for a device</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Device Type *</label>
                                <select class="form-select" name="edit_Type" id="editType" required>
                                    <option value="">Select Type</option>
                                    <option value="L1">L1</option>
                                    <option value="L2">L2</option>
                                    <option value="M1">M1</option>
                                    <option value="M2">M2</option>
                                    <option value="T1">T1</option>
                                    <option value="T2">T2</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           name="edit_Isissued" value="1" id="editIsissued" role="switch">
                                    <label class="form-check-label" for="editIsissued">
                                        Device Issued?
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           name="edit_dev_status" value="1" id="editDevStatus" role="switch">
                                    <label class="form-check-label" for="editDevStatus">
                                        Device is OK
                                    </label>
                                    <div class="form-text">Turn OFF if device is defective</div>
                                </div>
                            </div>
                            
                            <button type="submit" name="edit_device" class="btn btn-primary w-100">
                                ✏️ Update Device
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Devices</h5>
                        <h2><?php echo $stats['total'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">OK Devices</h5>
                        <h2><?php echo $stats['ok'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Issued Devices</h5>
                        <h2><?php echo $stats['issued'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <h5 class="card-title">Defective Devices</h5>
                        <h2><?php echo $stats['defective'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize Select2 for searchable dropdown
        $('#deviceSelect').select2({
            ajax: {
                url: 'add_device.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term,
                        ajax_search: true
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    return {
                        results: []
                    };
                }
            },
            minimumInputLength: 1,
            placeholder: 'Search device by SLNO or Type...',
            allowClear: true,
            width: '100%'
        });
        
        // Load device data when a device is selected
        $('#deviceSelect').on('change', function() {
            var selectedDevice = $(this).val();
            if(selectedDevice) {
                // Switch to Edit tab
                $('#edit-tab').tab('show');
                
                // Update URL
                var url = new URL(window.location.href);
                url.searchParams.set('load_device', selectedDevice);
                window.history.pushState({}, '', url);
                
                // Fetch device data via AJAX
                fetchDeviceData(selectedDevice);
            }
        });
        
        // Function to fetch device data
        function fetchDeviceData(slno) {
            $.ajax({
                url: 'add_device.php',
                method: 'GET',
                data: { 
                    load_device: slno,
                    get_data: true 
                },
                dataType: 'json',
                success: function(data) {
                    if(data.success) {
                        $('#editType').val(data.Type);
                        $('#editIsissued').prop('checked', data.Isissued == 1);
                        $('#editDevStatus').prop('checked', data.dev_status == 1);
                        
                        // Update the selected option text
                        var optionText = slno + ' (' + data.Type + ')';
                        $('#deviceSelect').html('<option value="' + slno + '" selected>' + optionText + '</option>');
                    }
                }
            });
        }
        
        // Pre-fill edit form if device data is available
        <?php if($editDeviceData): ?>
        $('#editType').val('<?php echo $editDeviceData['Type']; ?>');
        $('#editIsissued').prop('checked', <?php echo $editDeviceData['Isissued'] == 1 ? 'true' : 'false'; ?>);
        $('#editDevStatus').prop('checked', <?php echo $editDeviceData['dev_status'] == 1 ? 'true' : 'false'; ?>);
        
        // Ensure Edit tab is active
        $('#edit-tab').tab('show');
        <?php endif; ?>
        
        // Refresh device list function
        window.refreshDeviceList = function() {
            location.reload();
        };
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
        
        // Clear edit form when Add tab is clicked
        $('#add-tab').on('click', function() {
            window.history.pushState({}, '', 'add_device.php');
        });
    });
    </script>
</body>
</html>