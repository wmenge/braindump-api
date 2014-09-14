<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BrainDump - Administration</title>

  <!-- Bootstrap -->
  <link rel='stylesheet prefetch' href='/libs/bootstrap/3.2.0/css/bootstrap.min.css'>
  <link rel='stylesheet prefetch' href='/libs/bootstrap/3.2.0/css/bootstrap-theme.min.css'>
  
</head>
<body>

  <!-- Header -->
  <div class="navbar navbar-inverse navbar-static-top" role="navigation">
    <div class="navbar-header">
      <span class="navbar-brand">
        <span id="brain">Brain</span><span id="dump">Dump</span>
        | Administration
      </span>
    </div>

    <div class="navbar-collapse collapse">
      <ul class="nav navbar-nav">
        <li class="active"><a href="/admin">Home</a></li>
        <li><a href="#">Users <span class="badge"><?= $userCount ?></span></a></li>
        <li><a href="#">Notebooks <span class="badge"><?= $notebookCount ?></span></a></li>
        <li><a href="#">Notes <span class="badge"><?= $noteCount ?></span></a></li>
      </ul>
    </div><!--/.nav-collapse -->
  </div>
  
  <div class="container-fluid">

    <!-- Message area -->
    <div class="row">

     <div class="col-sm-12">

      <?php if(!empty($flash['success'])): ?>
      <div class="alert alert-success alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <strong>Success:</strong> <?= $flash['success'] ?>
      </div>
    <?php endif; ?>
    <?php if(!empty($flash['warning'])): ?>
    <div class="alert alert-warning alert-dismissible" role="alert">
      <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
      <strong>Warning:</strong> <?= $flash['warning'] ?>
    </div>
  <?php endif; ?>
  <?php if(!empty($flash['error'])): ?>
  <div class="alert alert-danger alert-dismissible" role="alert">
    <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
    <strong>Error:</strong> <?= $flash['error'] ?>
  </div>
<?php endif; ?>

</div>

</div>

<!-- First row: Export data | Import data | Setup database -->
<div class="row">

  <div class="col-sm-6">

    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">Export data</h3>
      </div>
      <div class="panel-body">
        <a href="/export" class="btn btn-default">Export data</a>
        <p class="help-block">Download a JSON dump of all notebooks and notes</p>
      </div>
    </div>

  </div>

  <div class="col-sm-6">

    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">Import data</h3>
      </div>
      <div class="panel-body">

        <form role="form" enctype="multipart/form-data" action="/import" method="POST">
          <div class="form-group">
            <input type="file" name="importFile" id="importFile">
          </div>
          <button type="submit" class="btn btn-default">Submit</button>
          <p class="help-block">Currently only Braindump JSON format is supported</p>
        </form>

      </div>
    </div>

  </div>

  <div class="col-sm-6">

    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">Data management</h3>
      </div>
      <div class="panel-body">

        <form role="form" action="/setup" method="POST">
          <div class="form-group">
            <label for="confirm">Confirm setup</label>
            <input type="text" class="form-control" name="confirm" id="confirm" placeholder="Type YES to confirm setup">
          </div>
          <button type="submit" class="btn btn-default">Setup Database</button>
        </form>

        <p class="help-block">All data will be lost! Type YES in the confirmation field to proceed.</p>
      </div>
    </div>

  </div>

  <div class="col-sm-6">
    
    <?php if($migrationNeeded): ?>

    <div class="panel panel-warning">
      <div class="panel-heading">
        <h3 class="panel-title">Database status</h3>
      </div>
      <div class="panel-body">
        <p>Database schema is not up to date and should be updated</p>
        <form role="form" action="/migrate" method="POST">
          <button type="submit" class="btn btn-default">Update Schema</button>
        </form>
        <p class="help-block">Current version: <?= $currentVersion ?>, available version: <?= $highestVersion ?></p>
        
      </div>
    </div>

  <?php else: ?>

  <div class="panel panel-success">
    <div class="panel-heading">
      <h3 class="panel-title">Database status</h3>
    </div>
    <div class="panel-body">
      <p>Database schema is up to date, no action needed</p>
      <p class="help-block">Current version: <?= $currentVersion ?></p>
    </div>
  </div>

<?php endif; ?>
</div>

</div>

</div>
</body>

 <!-- Bootstrap core JavaScript
 ================================================== -->
 <!-- Placed at the end of the document so the pages load faster -->
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
 <script src="/libs/bootstrap/3.2.0/js/bootstrap.min.js"></script>

 </html>