<div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">Application Settings</h3>
      </div>
      <div class="panel-body form-padding-right">
        <?php echo Former::text('app[url]')->label('URL')->value(isset($_ENV['APP_URL']) ? $_ENV['APP_URL'] : Request::root()); ?>

        <?php echo Former::checkbox('debug')
                ->label('Debug')
                ->text(trans('texts.enable'))
                ->check(config('app.debug')); ?>


      </div>
    </div>

    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">Database Connection</h3>
      </div>
      <div class="panel-body form-padding-right">
        <?php /*- Former::select('database[default]')->label('Driver')->options(['mysql' => 'MySQL', 'pgsql' => 'PostgreSQL', 'sqlite' => 'SQLite'])
                ->value(isset($_ENV['DB_TYPE']) ? $_ENV['DB_TYPE'] : 'mysql') -*/ ?>
        <?php echo Former::plaintext('Driver')->value('MySQL'); ?>

        <?php echo Former::text('database[type][host]')->label('Host')->value(isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost'); ?>

        <?php echo Former::text('database[type][database]')->label('Database')->value(isset($_ENV['DB_DATABASE']) ? $_ENV['DB_DATABASE'] : 'ninja'); ?>

        <?php echo Former::text('database[type][username]')->label('Username')->value(isset($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : 'ninja'); ?>

        <?php echo Former::password('database[type][password]')->label('Password')->value(isset($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : 'ninja'); ?>

        <?php echo Former::actions( Button::primary('Test connection')->small()->withAttributes(['onclick' => 'testDatabase()']), '&nbsp;&nbsp;<span id="dbTestResult"/>' ); ?>      
      </div>
    </div>

    <?php if(!isset($_ENV['POSTMARK_API_TOKEN'])): ?>
        <div class="panel panel-default">
          <div class="panel-heading">
            <h3 class="panel-title">Email Settings</h3>
          </div>
          <div class="panel-body form-padding-right">
            <?php echo Former::select('mail[driver]')->label('Driver')->options(['smtp' => 'SMTP', 'mail' => 'Mail', 'sendmail' => 'Sendmail', 'mailgun' => 'Mailgun'])
                     ->value(isset($_ENV['MAIL_DRIVER']) ? $_ENV['MAIL_DRIVER'] : 'smtp')->setAttributes(['onchange' => 'mailDriverChange()']); ?>

            <?php echo Former::text('mail[from][name]')->label('From Name')
                  ->value(isset($_ENV['MAIL_FROM_NAME']) ? $_ENV['MAIL_FROM_NAME'] : ''); ?>

            <?php echo Former::text('mail[username]')->label('Email')
                    ->value(isset($_ENV['MAIL_USERNAME']) ? $_ENV['MAIL_USERNAME'] : ''); ?>

            <div id="standardMailSetup">
              <?php echo Former::text('mail[host]')->label('Host')
                      ->value(isset($_ENV['MAIL_HOST']) ? $_ENV['MAIL_HOST'] : ''); ?>

              <?php echo Former::text('mail[port]')->label('Port')
                      ->value(isset($_ENV['MAIL_PORT']) ? $_ENV['MAIL_PORT'] : '587'); ?>

              <?php echo Former::select('mail[encryption]')->label('Encryption')->options(['tls' => 'TLS', 'ssl' => 'SSL'])
                      ->value(isset($_ENV['MAIL_ENCRYPTION']) ? $_ENV['MAIL_ENCRYPTION'] : 'tls'); ?>

              <?php echo Former::password('mail[password]')->label('Password')
                      ->value(isset($_ENV['MAIL_PASSWORD']) ? $_ENV['MAIL_PASSWORD'] : ''); ?>

            </div>
            <div id="mailgunMailSetup">
              <?php echo Former::text('mail[mailgun_domain]')->label('Mailgun Domain')
                      ->value(isset($_ENV['MAILGUN_DOMAIN']) ? $_ENV['MAILGUN_DOMAIN'] : ''); ?>

              <?php echo Former::text('mail[mailgun_secret]')->label('Mailgun Private Key')
                      ->value(isset($_ENV['MAILGUN_SECRET']) ? $_ENV['MAILGUN_SECRET'] : ''); ?>

            </div>
            <?php /* Former::actions( Button::primary('Send test email')->small()->withAttributes(['onclick' => 'testMail()']), '&nbsp;&nbsp;<span id="mailTestResult"/>' ) */ ?>
          </div>
        </div>
    <?php endif; ?>

  <script type="text/javascript">

    var db_valid = false
    var mail_valid = false
    mailDriverChange();

    function testDatabase()
    {
      var data = $("form").serialize() + "&test=db";
      
      // Show Progress Text
      $('#dbTestResult').html('Working...').css('color', 'black');

      // Send / Test Information
      $.post( "<?php echo e(URL::to('/setup')); ?>", data, function( data ) {
        var color = 'red';
        if(data == 'Success'){
          color = 'green';
          db_valid = true;
        }
        $('#dbTestResult').html(data).css('color', color);
      });

      return db_valid;
    }  

    function mailDriverChange() {
      if ($("select[name='mail[driver]'").val() == 'mailgun') {
        $("#standardMailSetup").hide();
        $("#standardMailSetup").children('select,input').prop('disabled',true);
        $("#mailgunMailSetup").show();
        $("#mailgunMailSetup").children('select,input').prop('disabled',false);

      } else {
        $("#standardMailSetup").show();
        $("#standardMailSetup").children('select,input').prop('disabled',false);

        $("#mailgunMailSetup").hide();
        $("#mailgunMailSetup").children('select,input').prop('disabled',true);

      }
    }

    function testMail()
    {      
      var data = $("form").serialize() + "&test=mail";
      
      // Show Progress Text
      $('#mailTestResult').html('Working...').css('color', 'black');

      // Send / Test Information
      $.post( "<?php echo e(URL::to('/setup')); ?>", data, function( data ) {
        var color = 'red';
        if(data == 'Sent'){
          color = 'green';
          mail_valid = true;
        }
        $('#mailTestResult').html(data).css('color', color);
      });
      
      return mail_valid;
    }

    // Prevent the Enter Button from working
    $("form").bind("keypress", function (e) {
      if (e.keyCode == 13) {
        return false;
      }
    });

  </script>