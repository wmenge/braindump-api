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

    <?= $menu ?>
  
  </div>
  
  <div class="container" role="main">
    <div class="row">
      <div class="col-sm-6">

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

    <?= $content ?>

  </div>
</body>

 <!-- Bootstrap core JavaScript
 ================================================== -->
 <!-- Placed at the end of the document so the pages load faster -->
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
 <script src="/libs/bootstrap/3.2.0/js/bootstrap.min.js"></script>

 </html>