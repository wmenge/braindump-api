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
          <input type="login" class="form-control input-lg" name="login" id="login" placeholder="Enter login" value="">
        </div>
        <div class="form-group">
          <input type="password" class="form-control input-lg" name="password" id="password" placeholder="Password">
        </div>
        <button type="submit" class="btn btn-primary btn-lg">Log in</button>
      </form>
    </div>

    <div class="jumbotron">
      <div class="page-header">
        <h3>Or..</h3>
      </div>

      <form action="/oauth2/login">
        <button type="submit" class="btn btn-primary btn-lg">Log in with GitHub</button>
      </form>

    </div>

  </div>

  <div class="col-sm-3"></div>

</div>