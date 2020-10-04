<div class="row">

  <div class="col-sm-3"></div>

  <div class="col-sm-6">

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
        <h3>Login</h3>
      
      <form action="/oauth2/github/login" style="display: inline-block;">
        <input type="hidden" value="<?= @$referer ?>" name="referer">
        <div class="form-group">
                <label>Login with:</label>
        </div>
        <button type="submit" class="btn btn-primary btn-lg">GitHub</button>
      </form>
      <label>or</label>

      <form action="/oauth2/google/login" style="display: inline-block;">
        <input type="hidden" value="<?= @$referer ?>" name="referer">
        <button type="submit" class="btn btn-primary btn-lg">Google</button>
      </form>

      <hr>
      
      <form role="form" action="/login" method="POST">
        <input type="hidden" value="<?= @$referer ?>" name="referer">
        <div class="form-group">
          <label>Login with credentials:</label>
        </div>
        <div class="form-group">
          <input type="login" class="form-control input-lg" name="login" id="login" placeholder="Enter login" value="">
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