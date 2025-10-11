<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Convert to donation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#fafafa; }
    .page { max-width:880px; margin:48px auto; }
  </style>
</head>
<body>
  <div class="page">
    <h2 class="mb-4">Convert to donation</h2>

    <!-- NOTE:
         - The item name below is a placeholder.
         - When backend is added, inject the real item name here.
    -->
    <p class="lead">
      Are you sure you want to convert <strong>Milk</strong> to donation?
    </p>

    <div class="card">
      <div class="card-body">
        <!-- Form is static for now; action/method will be added later -->
        <div class="mb-3">
          <label class="form-label">Pickup Location</label>
          <input class="form-control" placeholder="Enter pickup location (e.g., Lobby A)" />
          <!-- TIP: This will be a required field later in backend validation -->
        </div>

        <div class="mb-4">
          <label class="form-label">Pickup Duration</label>
          <input class="form-control" placeholder="Enter available time (e.g., Today 2–5 PM)" />
          <!-- TIP: You can switch to a select or date-time range later -->
        </div>

        <div class="d-flex gap-2">
          <!-- Cancel: go back to inventory list -->
          <a class="btn btn-outline-secondary" href="inventory.blade.php">Cancel</a>
          <!-- Convert: submit later; now just a visual button -->
          <button class="btn btn-primary" type="button">Convert</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
