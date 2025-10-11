<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Manage Food Inventory</title>
  <!-- Bootstrap 5 (CDN) for quick styling; can be replaced later -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    /* Simple layout to mimic the wireframe */
    body { background:#fafafa; }
    .app-shell { min-height:100vh; display:flex; flex-direction:column; }
    .app-main { flex:1; display:flex; gap:1rem; }
    .app-sidebar { width:220px; background:#fff; border-right:1px solid #eee; }
    .app-content { flex:1; padding:1rem 0; }
    .sidebar-link { display:block; padding:.65rem 1rem; color:#333; text-decoration:none; }
    .sidebar-link.active { background:#f0f4ff; font-weight:600; }
    .table-actions a { margin-right:.75rem; }
    footer { background:#666; color:#fff; }
    .brand { font-weight:700; letter-spacing:.3px; }
  </style>
</head>
<body>
<div class="app-shell">
  <!-- Top navbar -->
  <nav class="navbar navbar-expand-lg bg-white border-bottom">
    <div class="container-fluid">
      <span class="navbar-brand brand">SmartPlate</span>

      <!-- Primary nav to match the wireframe -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="#">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Food Inventory</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Track and Report</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Plan Weekly Meals</a></li>
      </ul>

      <!-- Right-side icons (placeholders) -->
      <div class="d-flex align-items-center gap-3">
        <a class="text-decoration-none" href="#" title="Notifications">
          <i class="bi bi-bell"></i>
          <span class="badge bg-danger rounded-pill">9</span> 
        </a>
        <a class="text-decoration-none" href="#" title="Profile">
          <i class="bi bi-person-circle fs-5"></i>
        </a>
      </div>
    </div>
  </nav>

  <!-- Main area -->
  <div class="container-fluid app-main">
    <!-- Left sidebar -->
    <aside class="app-sidebar">
      <div class="pt-3">
        <!-- Sidebar links; use .active for the current page -->
        <a class="sidebar-link active" href="#">Inventory</a>
        <a class="sidebar-link" href="#">Donation</a>
        <a class="sidebar-link" href="#">Browse Food Items</a>
      </div>
    </aside>

    <!-- Page content -->
    <section class="app-content">
      <div class="container">
        <!-- Header row with title + actions -->
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h5 class="mb-0">Food Inventory</h5>
          <div class="d-flex align-items-center gap-2">
            <!-- Filter button placeholder -->
            <button class="btn btn-outline-secondary btn-sm" type="button">
              <i class="bi bi-funnel"></i> Filters
            </button>
            <!-- Add new item button (no backend yet) -->
            <a class="btn btn-primary btn-sm" href="#add-new-item">
              <i class="bi bi-plus-lg"></i> Add new item
            </a>
          </div>
        </div>

        <!-- Table card -->
        <div class="card">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:40%">Item Name <i class="bi bi-arrow-down-up ms-1"></i></th>
                  <th style="width:25%">Quantity <i class="bi bi-arrow-down-up ms-1"></i></th>
                  <th style="width:25%">Expiry Date <i class="bi bi-arrow-down-up ms-1"></i></th>
                  <th class="text-end" style="width:10%">Actions</th>
                </tr>
              </thead>
              <tbody>
                <!-- Static demo rows. Replace later with server data. -->
                <tr>
                  <td>Milk</td>
                  <td>1 litres</td>
                  <td>12/10/2025</td>
                  <td class="text-end table-actions">
                    <!-- NOTE: These are placeholders; link to real routes later -->
                    <a href="#edit-milk" class="link-primary">Edit</a>
                    <a href="#delete-milk" class="link-danger">Delete</a>
                    <a href="convert_donation.html?item=Milk" class="link-success">Convert to donation</a>
                  </td>
                </tr>
                <tr>
                  <td>Bread</td>
                  <td>2 packs</td>
                  <td>13/10/2025</td>
                  <td class="text-end table-actions">
                    <a href="#edit-bread" class="link-primary">Edit</a>
                    <a href="#delete-bread" class="link-danger">Delete</a>
                    <a href="convert_donation.html?item=Bread" class="link-success">Convert to donation</a>
                  </td>
                </tr>
                <tr>
                  <td>Apple</td>
                  <td>5 pcs</td>
                  <td>15/10/2025</td>
                  <td class="text-end table-actions">
                    <a href="#edit-apple" class="link-primary">Edit</a>
                    <a href="#delete-apple" class="link-danger">Delete</a>
                    <a href="convert_donation.html?item=Apple" class="link-success">Convert to donation</a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Optional: a subtle bottom loading bar placeholder -->
          <div class="p-2">
            <!-- Purely visual placeholder to mimic your wireframe loading bar -->
            <div class="progress" style="height:6px;">
              <div class="progress-bar" style="width:35%;"></div>
            </div>
          </div>
        </div>

        <!-- Anchor target for the Add button (placeholder form) -->
        <div id="add-new-item" class="mt-4">
          <!-- SIMPLE PLACEHOLDER FORM (front-end only) -->
          <!-- PURPOSE: show where the "Add Item" form will live; hook backend later -->
          <div class="card">
            <div class="card-body">
              <h6 class="card-title">Add Item (placeholder)</h6>
              <div class="row g-2">
                <div class="col-md-4">
                  <label class="form-label">Item name</label>
                  <input class="form-control" placeholder="e.g., Milk" />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Quantity</label>
                  <input class="form-control" placeholder="e.g., 1" />
                </div>
                <div class="col-md-2">
                  <label class="form-label">Unit</label>
                  <select class="form-select">
                    <option>pcs</option>
                    <option>packs</option>
                    <option>litres</option>
                    <option>g</option>
                    <option>ml</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Expiry date</label>
                  <input type="date" class="form-control" />
                </div>
              </div>
              <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm" type="button">Save (demo)</button>
                <button class="btn btn-outline-secondary btn-sm" type="button">Reset</button>
                <!-- EXPLANATION: These buttons do nothing yet; backend & JS will be added later -->
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>
  </div>

  <!-- Footer -->
  <footer class="py-3 mt-4">
    <div class="container d-flex justify-content-between small">
      <span class="brand">SmartPlate</span>
      <span>HELPBIT216G6 © 2025. All rights reserved.</span>
      <div class="d-flex gap-3">
        <a class="link-light text-decoration-none" href="#">Food Inventory</a>
        <a class="link-light text-decoration-none" href="#">Track and Report</a>
        <a class="link-light text-decoration-none" href="#">Plan Meals</a>
      </div>
    </div>
  </footer>
</div>
</body>
</html>
