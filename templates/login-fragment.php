<div class="row">

  <div class="col-sm-3"></div>

  <div class="col-sm-6">

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
      <div class="page-header">
        <h3>Login <small> to the BrainDump Administration Panel</small></h3>
      </div>
      <form role="form" action="/admin/login" method="POST">
        <div class="form-group">
          <input type="email" class="form-control input-lg" name="login" id="login" placeholder="Enter email" value="administrator@braindump-api.local">
        </div>
        <div class="form-group">
          <input type="password" class="form-control input-lg" name="password" id="password" placeholder="Password">
        </div>
        <button type="submit" class="btn btn-primary btn-lg">Log in</button>
      </form>
    </div>

  </div>

  <div class="col-sm-3"></div>

</div>