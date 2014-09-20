<div class="row">

  <div class="col-sm-6">

    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">Export data</h3>
      </div>
      <div class="panel-body">
        <a href="/admin/export" class="btn btn-default">Export data</a>
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

        <form role="form" enctype="multipart/form-data" action="/admin/import" method="POST">
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

        <form role="form" action="/admin/setup" method="POST">
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
          <form role="form" action="/admin/migrate" method="POST">
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
