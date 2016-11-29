<?php $__env->startSection('head'); ?>

  <link href="<?php echo e(asset('css/built.css')); ?>?no_cache=<?php echo e(NINJA_VERSION); ?>" rel="stylesheet" type="text/css"/>
  <link href="<?php echo e(asset('css/customize.css')); ?>?no_cache=<?php echo e(NINJA_VERSION); ?>" rel="stylesheet" type="text/css"/>


  <style type="text/css">
    <?php if(Auth::check() && Auth::user()->dark_mode): ?>
        body {
            background: #000 !important;
            color: white !important;
        }

        .panel-body {
            background: #272822 !important;
            /*background: #e6e6e6 !important;*/
        }

        .panel-default {
            border-color: #444;
        }
    <?php endif; ?>

  </style>

<script type="text/javascript">

  <?php if(!Auth::check() || !Auth::user()->registered): ?>
  function validateSignUp(showError)
  {
    var isFormValid = true;
    $(['first_name','last_name','email','password']).each(function(i, field) {
      var $input = $('form.signUpForm #new_'+field),
      val = $.trim($input.val());
      var isValid = val && val.length >= (field == 'password' ? 6 : 1);
      if (isValid && field == 'email') {
        isValid = isValidEmailAddress(val);
      }
      if (isValid) {
        $input.closest('div.form-group').removeClass('has-error').addClass('has-success');
      } else {
        isFormValid = false;
        $input.closest('div.form-group').removeClass('has-success');
        if (showError) {
          $input.closest('div.form-group').addClass('has-error');
        }
      }
    });

    if (!$('#terms_checkbox').is(':checked')) {
      isFormValid = false;
    }

    $('#saveSignUpButton').prop('disabled', !isFormValid);

    return isFormValid;
  }

  function validateServerSignUp()
  {
    if (!validateSignUp(true)) {
      return;
    }

    $('#signUpDiv, #signUpFooter').hide();
    $('#working').show();

    $.ajax({
      type: 'POST',
      url: '<?php echo e(URL::to('signup/validate')); ?>',
      data: 'email=' + $('form.signUpForm #new_email').val(),
      success: function(result) {
        if (result == 'available') {
          submitSignUp();
        } else {
          $('#errorTaken').show();
          $('form.signUpForm #new_email').closest('div.form-group').removeClass('has-success').addClass('has-error');
          $('#signUpDiv, #signUpFooter').show();
          $('#working').hide();
        }
      }
    });
  }

  function submitSignUp() {
    $.ajax({
      type: 'POST',
      url: '<?php echo e(URL::to('signup/submit')); ?>',
      data: 'new_email=' + encodeURIComponent($('form.signUpForm #new_email').val()) +
      '&new_password=' + encodeURIComponent($('form.signUpForm #new_password').val()) +
      '&new_first_name=' + encodeURIComponent($('form.signUpForm #new_first_name').val()) +
      '&new_last_name=' + encodeURIComponent($('form.signUpForm #new_last_name').val()) +
      '&go_pro=' + $('#go_pro').val(),
      success: function(result) {
        if (result) {
          handleSignedUp();
          NINJA.isRegistered = true;
          $('#signUpButton').hide();
          $('#myAccountButton').html(result);
        }
        $('#signUpSuccessDiv, #signUpFooter, #closeSignUpButton').show();
        $('#working, #saveSignUpButton').hide();
      }
    });
  }
  <?php endif; ?>

  function handleSignedUp() {
      localStorage.setItem('guest_key', '');
      fbq('track', 'CompleteRegistration');
      window._fbq.push(['track', '<?php echo e(env('FACEBOOK_PIXEL_SIGN_UP')); ?>', {'value':'0.00','currency':'USD'}]);
      trackEvent('/account', '/signed_up');
  }

  function checkForEnter(event)
  {
    if (event.keyCode === 13){
      event.preventDefault();
      validateServerSignUp();
      return false;
    }
  }

  function logout(force)
  {
    if (force) {
      NINJA.formIsChanged = false;
    }

    if (force || NINJA.isRegistered) {
      window.location = '<?php echo e(URL::to('logout')); ?>';
    } else {
      $('#logoutModal').modal('show');
    }
  }

  function showSignUp() {
    $('#signUpModal').modal('show');
  }

  function hideSignUp() {
    $('#signUpModal').modal('hide');
  }

  function buyProduct(affiliateKey, productId) {
    window.open('<?php echo e(Utils::isNinjaDev() ? '' : NINJA_APP_URL); ?>/license?affiliate_key=' + affiliateKey + '&product_id=' + productId + '&return_url=' + window.location);
  }

  function hideMessage() {
    $('.alert-info').fadeOut();
    $.get('/hide_message', function(response) {
      console.log('Reponse: %s', response);
    });
  }

  function wordWrapText(value, width)
  {
    <?php if(Auth::user()->account->auto_wrap): ?>
    var doc = new jsPDF('p', 'pt');
    doc.setFont('Helvetica','');
    doc.setFontSize(10);

    var lines = value.split("\n");
    for (var i = 0; i < lines.length; i++) {
      var numLines = doc.splitTextToSize(lines[i], width).length;
      if (numLines <= 1) continue;
      var j = 0; space = lines[i].length;
      while (j++ < lines[i].length) {
        if (lines[i].charAt(j) === ' ') space = j;
      }
      if (space == lines[i].length) space = width/6;
      lines[i + 1] = lines[i].substring(space + 1) + ' ' + (lines[i + 1] || '');
      lines[i] = lines[i].substring(0, space);
    }

    var newValue = (lines.join("\n")).trim();

    if (value == newValue) {
      return newValue;
    } else {
      return wordWrapText(newValue, width);
    }
    <?php else: ?>
    return value;
    <?php endif; ?>
  }

  function setSignupEnabled(enabled) {
    $('.signup-form input[type=text]').prop('disabled', !enabled);
    if (enabled) {
        $('.signup-form a.btn').removeClass('disabled');
    } else {
        $('.signup-form a.btn').addClass('disabled');
    }
  }

  function setSocialLoginProvider(provider) {
    localStorage.setItem('auth_provider', provider);
  }

  window.loadedSearchData = false;
  function onSearchFocus() {
    $('#search').typeahead('val', '');
    $('#search-form').show();

    if (!window.loadedSearchData) {
        window.loadedSearchData = true;
        trackEvent('/activity', '/search');
        var request = $.get('<?php echo e(URL::route('get_search_data')); ?>', function(data) {
          $('#search').typeahead({
            hint: true,
            highlight: true,
          }
          <?php if(Auth::check() && Auth::user()->account->custom_client_label1): ?>
          ,{
            name: 'data',
            limit: 3,
            display: 'value',
            source: searchData(data['<?php echo e(Auth::user()->account->custom_client_label1); ?>'], 'tokens'),
            templates: {
              header: '&nbsp;<span style="font-weight:600;font-size:16px"><?php echo e(Auth::user()->account->custom_client_label1); ?></span>'
            }
          }
          <?php endif; ?>
          <?php if(Auth::check() && Auth::user()->account->custom_client_label2): ?>
          ,{
            name: 'data',
            limit: 3,
            display: 'value',
            source: searchData(data['<?php echo e(Auth::user()->account->custom_client_label2); ?>'], 'tokens'),
            templates: {
              header: '&nbsp;<span style="font-weight:600;font-size:16px"><?php echo e(Auth::user()->account->custom_client_label2); ?></span>'
            }
          }
          <?php endif; ?>
          <?php foreach(['clients', 'contacts', 'invoices', 'quotes', 'navigation'] as $type): ?>
          ,{
            name: 'data',
            limit: 3,
            display: 'value',
            source: searchData(data['<?php echo e($type); ?>'], 'tokens', true),
            templates: {
              header: '&nbsp;<span style="font-weight:600;font-size:16px"><?php echo e(trans("texts.{$type}")); ?></span>'
            }
          }
          <?php endforeach; ?>
          ).on('typeahead:selected', function(element, datum, name) {
            window.location = datum.url;
          }).focus();
        });

        request.error(function(httpObj, textStatus) {
            // if the session has expried show login page
            if (httpObj.status == 401) {
                location.reload();
            }
        });
    }
  }

  $(function() {
    window.setTimeout(function() {
        $(".alert-hide").fadeOut();
    }, 3000);

    /* Set the defaults for Bootstrap datepicker */
    $.extend(true, $.fn.datepicker.defaults, {
        //language: '<?php echo e($appLanguage); ?>', // causes problems with some languages (ie, fr_CA) if the date includes strings (ie, July 31, 2016)
        weekStart: <?php echo e(Session::get('start_of_week')); ?>

    });

    if (isStorageSupported()) {
      <?php if(Auth::check() && !Auth::user()->registered): ?>
      localStorage.setItem('guest_key', '<?php echo e(Auth::user()->password); ?>');
      <?php endif; ?>
    }

    <?php if(!Auth::check() || !Auth::user()->registered): ?>
    validateSignUp();

    $('#signUpModal').on('shown.bs.modal', function () {
      trackEvent('/account', '/view_sign_up');
      $(['first_name','last_name','email','password']).each(function(i, field) {
        var $input = $('form.signUpForm #new_'+field);
        if (!$input.val()) {
          $input.focus();
          return false;
        }
      });
    })
    <?php endif; ?>

    <?php if(Auth::check() && !Utils::isNinja() && !Auth::user()->registered): ?>
      $('#closeSignUpButton').hide();
      showSignUp();
    <?php elseif(Session::get('sign_up') || Input::get('sign_up')): ?>
      showSignUp();
    <?php endif; ?>

    $('ul.navbar-settings, ul.navbar-search').hover(function () {
        if ($('.user-accounts').css('display') == 'block') {
            $('.user-accounts').dropdown('toggle');
        }
    });

    <?php echo $__env->yieldContent('onReady'); ?>

    <?php if(Input::has('focus')): ?>
        $('#<?php echo e(Input::get('focus')); ?>').focus();
    <?php endif; ?>

    // Ensure terms is checked for sign up form
    <?php if(Auth::check() && !Auth::user()->registered): ?>
        setSignupEnabled(false);
        $("#terms_checkbox").change(function() {
            setSignupEnabled(this.checked);
        });
    <?php endif; ?>

    // Focus the search input if the user clicks forward slash
    $('#search').focusin(onSearchFocus);

    $('body').keypress(function(event) {
        if (event.which == 47 && !$('*:focus').length) {
            event.preventDefault();
            $('#search').focus();
        }
    });

    // manage sidebar state
    $("#left-menu-toggle").click(function(e) {
        e.preventDefault();
        $("#wrapper").toggleClass("toggled-left");

        var toggled = $("#wrapper").hasClass("toggled-left") ? '1' : '0';
        $.get('<?php echo e(url('save_sidebar_state')); ?>?show_left=' + toggled);
    });

    $("#right-menu-toggle").click(function(e) {
        e.preventDefault();
        $("#wrapper").toggleClass("toggled-right");

        var toggled = $("#wrapper").hasClass("toggled-right") ? '1' : '0';
        $.get('<?php echo e(url('save_sidebar_state')); ?>?show_right=' + toggled);
    });

    if (window.location.hash) {
        setTimeout(function() {
            $('.nav-tabs a[href="' + window.location.hash + '"]').tab('show');
        }, 1);
    }

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href") // activated tab
        if (history.pushState) {
            history.pushState(null, null, target);
        }
    });

  });

</script>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('body'); ?>

<nav class="navbar navbar-default navbar-fixed-top" role="navigation" style="height:60px;">

    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-collapse-1">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a href="#" id="left-menu-toggle" class="menu-toggle" title="<?php echo e(trans('texts.toggle_navigation')); ?>">
          <div class="navbar-brand">
                <i class="fa fa-bars hide-phone" style="width:32px;padding-top:2px;float:left"></i>
                <?php /* Per our license, please do not remove or modify this link. */ ?>
                <!--<img src="<?php echo e(asset('images/invoiceninja-logo.png')); ?>" width="193" height="25" style="float:left"/>//-->
          </div>
      </a>
    </div>

    <a id="right-menu-toggle" class="menu-toggle hide-phone pull-right" title="<?php echo e(trans('texts.toggle_history')); ?>" style="cursor:pointer">
      <div class="fa fa-bars"></div>
    </a>

    <div class="collapse navbar-collapse" id="navbar-collapse-1">
      <div class="navbar-form navbar-right">

        <?php if(Auth::check()): ?>
          <?php if(!Auth::user()->registered): ?>
            <?php echo Button::success(trans('texts.sign_up'))->withAttributes(array('id' => 'signUpButton', 'data-toggle'=>'modal', 'data-target'=>'#signUpModal', 'style' => 'max-width:100px;;overflow:hidden'))->small(); ?> &nbsp;
          <?php elseif(Utils::isNinjaProd() && (!Auth::user()->isPro() || Auth::user()->isTrial())): ?>
            <?php echo Button::success(trans('texts.plan_upgrade'))->asLinkTo(url('/settings/account_management?upgrade=true'))->withAttributes(array('style' => 'max-width:100px;overflow:hidden'))->small(); ?> &nbsp;
          <?php endif; ?>
        <?php endif; ?>

        <div class="btn-group user-dropdown">
          <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
            <div id="myAccountButton" class="ellipsis" style="max-width:<?php echo e(Utils::hasFeature(FEATURE_USERS) ? '130' : '100'); ?>px;">
                <?php if(session(SESSION_USER_ACCOUNTS) && count(session(SESSION_USER_ACCOUNTS))): ?>
                    <?php echo e(Auth::user()->account->getDisplayName()); ?>

                <?php else: ?>
                    <?php echo e(Auth::user()->getDisplayName()); ?>

                <?php endif; ?>
              <span class="caret"></span>
            </div>
          </button>
          <ul class="dropdown-menu user-accounts">
            <?php if(session(SESSION_USER_ACCOUNTS)): ?>
                <?php foreach(session(SESSION_USER_ACCOUNTS) as $item): ?>
                    <?php if($item->user_id == Auth::user()->id): ?>
                        <?php echo $__env->make('user_account', [
                            'user_account_id' => $item->id,
                            'user_id' => $item->user_id,
                            'account_name' => $item->account_name,
                            'user_name' => $item->user_name,
                            'logo_url' => isset($item->logo_url) ? $item->logo_url : "",
                            'selected' => true,
                        ], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php foreach(session(SESSION_USER_ACCOUNTS) as $item): ?>
                    <?php if($item->user_id != Auth::user()->id): ?>
                        <?php echo $__env->make('user_account', [
                            'user_account_id' => $item->id,
                            'user_id' => $item->user_id,
                            'account_name' => $item->account_name,
                            'user_name' => $item->user_name,
                            'logo_url' => isset($item->logo_url) ? $item->logo_url : "",
                            'selected' => false,
                        ], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <?php echo $__env->make('user_account', [
                    'account_name' => Auth::user()->account->name ?: trans('texts.untitled'),
                    'user_name' => Auth::user()->getDisplayName(),
                    'logo_url' => Auth::user()->account->getLogoURL(),
                    'selected' => true,
                ], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
            <?php endif; ?>
            <li class="divider"></li>
            <?php if(Utils::isAdmin()): ?>
              <?php if(count(session(SESSION_USER_ACCOUNTS)) > 1): ?>
                  <li><?php echo link_to('/manage_companies', trans('texts.manage_companies')); ?></li>
              <?php elseif(!session(SESSION_USER_ACCOUNTS) || count(session(SESSION_USER_ACCOUNTS)) < 5): ?>
                  <li><?php echo link_to('/login?new_company=true', trans('texts.add_company')); ?></li>
              <?php endif; ?>
            <?php endif; ?>
            <li><?php echo link_to('#', trans('texts.logout'), array('onclick'=>'logout()')); ?></li>
          </ul>
        </div>

      </div>

      <form id="search-form" class="navbar-form navbar-right" role="search">
        <div class="form-group">
          <input type="text" id="search" style="width: 240px;padding-top:0px;padding-bottom:0px"
            class="form-control" placeholder="<?php echo e(trans('texts.search') . ': ' . trans('texts.search_hotkey')); ?>">
        </div>
      </form>

      <?php if(false && Utils::isAdmin()): ?>
      <ul class="nav navbar-nav navbar-right">
        <li class="dropdown">
           <?php $__env->startSection('self-updater'); ?>
            <a href="<?php echo e(URL::to('self-update')); ?>" class="dropdown-toggle">
              <span class="glyphicon glyphicon-cloud-download" title="<?php echo e(trans('texts.update_invoiceninja_title')); ?>"></span>
            </a>
          <?php echo $__env->yieldSection(); ?>
        </li>
      </ul>
      <?php endif; ?>

      <ul class="nav navbar-nav hide-non-phone" style="font-weight: bold">
        <?php foreach([
            'dashboard' => false,
            'clients' => false,
            'quotes' => false,
            'invoices' => false,
            'recurring_invoices' => 'recurring',
            'payments' => false,
            'settings' => false,
            'firstreport' => false,
        ] as $key => $value): ?>
            <?php echo Form::nav_link($key, $value ?: $key); ?>

        <?php endforeach; ?>
      </ul>
    </div><!-- /.navbar-collapse -->

</nav>



<div id="wrapper" class='<?php echo session(SESSION_LEFT_SIDEBAR) ? 'toggled-left' : ''; ?> <?php echo session(SESSION_RIGHT_SIDEBAR, true) ? 'toggled-right' : ''; ?>'>

    <!-- Sidebar -->
    <div id="left-sidebar-wrapper" class="hide-phone">
        <ul class="sidebar-nav">
            <?php foreach([
                'dashboard',
                'clients',
                'invoices',
                'payments',
                'recurring_invoices',
                'quotes',
                'settings',
                'firstreport'
            ] as $option): ?>
            <li class="<?php echo e(Request::is("{$option}*") ? 'active' : ''); ?>">
                <?php if($option == 'settings'): ?>
                    <a type="button" class="btn btn-default btn-sm pull-right"
                        href="<?php echo e(url(NINJA_DOCS_URL)); ?>" target="_blank">
                        <i class="fa fa-question-circle" style="width:20px" title="<?php echo e(trans('texts.help')); ?>"></i>
                    </a>
                <?php elseif($option != 'dashboard'): ?>
                    <?php if(Auth::user()->can('create', substr($option, 0, -1))): ?>
                        <a type="button" class="btn btn-primary btn-sm pull-right"
                            href="<?php echo e(url("/{$option}/create")); ?>">
                            <i class="fa fa-plus-circle" style="width:20px" title="<?php echo e(trans('texts.create_new')); ?>"></i>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="<?php echo e(url($option == 'recurring' ? 'recurring_invoice' : $option)); ?>"
                    style="font-size:14px; padding-top:4px; padding-bottom:4px"
                    class="<?php echo e(Request::is("{$option}*") ? 'active' : ''); ?>">
                    <i class="fa fa-<?php echo e(\App\Models\EntityModel::getIcon($option)); ?>" style="width:46px; padding-right:10px"></i>
                    <?php if($option != 'firstreport'): ?>
                      <?php echo e(($option == 'recurring_invoices') ? trans('texts.recurring') : trans("texts.{$option}")); ?>

                    <?php endif; ?>

                    <?php if($option == 'firstreport'): ?>
                      <?php echo e(($option == 'recurring_invoices') ? trans('texts.recurring') : trans("Report")); ?>

                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <!-- /#left-sidebar-wrapper -->

    <div id="right-sidebar-wrapper" class="hide-phone" style="overflow-y:hidden">
        <ul class="sidebar-nav">
            <?php echo \App\Libraries\HistoryUtils::renderHtml(Auth::user()->account_id); ?>

        </ul>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <div class="container-fluid">

          <?php echo $__env->make('partials.warn_session', ['redirectTo' => '/dashboard'], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

          <?php if(Session::has('warning')): ?>
          <div class="alert alert-warning"><?php echo Session::get('warning'); ?></div>
          <?php endif; ?>

          <?php if(Session::has('message')): ?>
            <div class="alert alert-info alert-hide">
              <?php echo e(Session::get('message')); ?>

            </div>
          <?php elseif(Session::has('news_feed_message')): ?>
            <div class="alert alert-info">
              <?php echo Session::get('news_feed_message'); ?>

              <a href="#" onclick="hideMessage()" class="pull-right"><?php echo e(trans('texts.hide')); ?></a>
            </div>
          <?php endif; ?>

          <?php if(Session::has('error')): ?>
              <div class="alert alert-danger"><?php echo Session::get('error'); ?></div>
          <?php endif; ?>

          <?php if(!isset($showBreadcrumbs) || $showBreadcrumbs): ?>
            <?php echo Form::breadcrumbs(isset($entityStatus) ? $entityStatus : ''); ?>

          <?php endif; ?>
          
          <?php echo $__env->yieldContent('content'); ?>

          <br/>
          <!--
          <div class="row">
            <div class="col-md-12">

              <?php if(Utils::isNinjaProd()): ?>
                <?php if(Auth::check() && Auth::user()->isTrial()): ?>
                  <?php echo trans(Auth::user()->account->getCountTrialDaysLeft() == 0 ? 'texts.trial_footer_last_day' : 'texts.trial_footer', [
                          'count' => Auth::user()->account->getCountTrialDaysLeft(),
                          'link' => link_to('/settings/account_management?upgrade=true', trans('texts.click_here'))
                      ]); ?>

                <?php endif; ?>
              <?php else: ?>

                <?php echo e(trans('texts.powered_by')); ?>

                <?php /* Per our license, please do not remove or modify this section. */ ?>
                <?php echo link_to('https://www.ezinvoice.lu/?utm_source=powered_by', 'InvoiceNinja.com', ['target' => '_blank', 'title' => 'invoiceninja.com']); ?> -
                <?php echo link_to(RELEASES_URL, 'v' . NINJA_VERSION, ['target' => '_blank', 'title' => trans('texts.trello_roadmap')]); ?> |
                <?php if(Auth::user()->account->hasFeature(FEATURE_WHITE_LABEL)): ?>
                  <?php echo e(trans('texts.white_labeled')); ?>

                <?php else: ?>
                  <a href="#" onclick="loadImages('#whiteLabelModal');$('#whiteLabelModal').modal('show');"><?php echo e(trans('texts.white_label_link')); ?></a>

                  <div class="modal fade" id="whiteLabelModal" tabindex="-1" role="dialog" aria-labelledby="whiteLabelModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                          <h4 class="modal-title" id="myModalLabel"><?php echo e(trans('texts.white_label_header')); ?></h4>
                        </div>

                        <div class="panel-body">
                          <p><?php echo e(trans('texts.white_label_text', ['price' => WHITE_LABEL_PRICE])); ?></p>
                          <div class="row">
                              <div class="col-md-6">
                                  <h4><?php echo e(trans('texts.before')); ?></h4>
                                  <img src="<?php echo e(BLANK_IMAGE); ?>" data-src="<?php echo e(asset('images/pro_plan/white_label_before.png')); ?>" width="100%" alt="before">
                              </div>
                              <div class="col-md-6">
                                  <h4><?php echo e(trans('texts.after')); ?></h4>
                                  <img src="<?php echo e(BLANK_IMAGE); ?>" data-src="<?php echo e(asset('images/pro_plan/white_label_after.png')); ?>" width="100%" alt="after">
                              </div>
                          </div><br/>
                          <p><?php echo trans('texts.reseller_text', ['email' => HTML::mailto('contact@ezlux.lu')]); ?></p>
                        </div>

                        <div class="modal-footer" id="signUpFooter" style="margin-top: 0px">
                          <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo e(trans('texts.close')); ?> </button>
                          <?php /* DropdownButton::success_lg(trans('texts.buy'), [
                              ['url' => URL::to(""), 'label' => trans('texts.pay_with_paypal')],
                              ['url' => URL::to(""), 'label' => trans('texts.pay_with_card')]
                          ])->addClass('btn-lg') */ ?>
                          <button type="button" class="btn btn-primary" onclick="buyProduct('<?php echo e(WHITE_LABEL_AFFILIATE_KEY); ?>', '<?php echo e(PRODUCT_WHITE_LABEL); ?>')"><?php echo e(trans('texts.buy')); ?> </button>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
              <?php endif; ?>
            </div>
            //-->
        </div>

    </div>
    <!-- /#page-content-wrapper -->
</div>


<?php if(!Auth::check() || !Auth::user()->registered): ?>
<div class="modal fade" id="signUpModal" tabindex="-1" role="dialog" aria-labelledby="signUpModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="myModalLabel"><?php echo e(trans('texts.sign_up')); ?></h4>
      </div>

      <div style="background-color: #fff; padding-right:20px" id="signUpDiv" onkeyup="validateSignUp()" onclick="validateSignUp()" onkeydown="checkForEnter(event)">
        <br/>

        <?php echo Former::open('signup/submit')->addClass('signUpForm')->autocomplete('on'); ?>


        <?php if(Auth::check()): ?>
        <?php echo Former::populateField('new_first_name', Auth::user()->first_name); ?>

        <?php echo Former::populateField('new_last_name', Auth::user()->last_name); ?>

        <?php echo Former::populateField('new_email', Auth::user()->email); ?>

        <?php endif; ?>

        <div style="display:none">
          <?php echo Former::text('path')->value(Request::path()); ?>

          <?php echo Former::text('go_pro'); ?>

        </div>


        <div class="row signup-form">
            <div class="col-md-11 col-md-offset-1">
                <?php echo Former::checkbox('terms_checkbox')->label(' ')->text(trans('texts.agree_to_terms', ['terms' => '<a href="'.URL::to('terms').'" target="_blank">'.trans('texts.terms_of_service').'</a>']))->raw(); ?>

                <br/>
            </div>
            <?php if(Utils::isOAuthEnabled()): ?>
                <div class="col-md-4 col-md-offset-1">
                    <h4><?php echo e(trans('texts.sign_up_using')); ?></h4><br/>
                    <?php foreach(App\Services\AuthService::$providers as $provider): ?>
                    <a href="<?php echo e(URL::to('auth/' . $provider)); ?>" class="btn btn-primary btn-block"
                        onclick="setSocialLoginProvider('<?php echo e(strtolower($provider)); ?>')" id="<?php echo e(strtolower($provider)); ?>LoginButton">
                        <i class="fa fa-<?php echo e(strtolower($provider)); ?>"></i> &nbsp;
                        <?php echo e($provider); ?>

                    </a>
                    <?php endforeach; ?>
                </div>
                <div class="col-md-1">
                    <div style="border-right:thin solid #CCCCCC;height:110px;width:8px;margin-bottom:10px;"></div>
                    <?php echo e(trans('texts.or')); ?>

                    <div style="border-right:thin solid #CCCCCC;height:110px;width:8px;margin-top:10px;"></div>
                </div>
                <div class="col-md-6">
            <?php else: ?>
                <div class="col-md-12">
            <?php endif; ?>
                <?php echo e(Former::setOption('TwitterBootstrap3.labelWidths.large', 1)); ?>

                <?php echo e(Former::setOption('TwitterBootstrap3.labelWidths.small', 1)); ?>


                <?php echo Former::text('new_first_name')
                        ->placeholder(trans('texts.first_name'))
                        ->autocomplete('given-name')
                        ->label(' '); ?>

                <?php echo Former::text('new_last_name')
                        ->placeholder(trans('texts.last_name'))
                        ->autocomplete('family-name')
                        ->label(' '); ?>

                <?php echo Former::text('new_email')
                        ->placeholder(trans('texts.email'))
                        ->autocomplete('email')
                        ->label(' '); ?>

                <?php echo Former::password('new_password')
                        ->placeholder(trans('texts.password'))
                        ->label(' '); ?>


                <?php echo e(Former::setOption('TwitterBootstrap3.labelWidths.large', 4)); ?>

                <?php echo e(Former::setOption('TwitterBootstrap3.labelWidths.small', 4)); ?>

            </div>

            <div class="col-md-11 col-md-offset-1">
                <div style="padding-top:20px;padding-bottom:10px;"><?php echo e(trans('texts.trial_message')); ?></div>
            </div>
        </div>

        <?php echo Former::close(); ?>




        <center><div id="errorTaken" style="display:none">&nbsp;<br/><?php echo e(trans('texts.email_taken')); ?></div></center>
        <br/>

      </div>

      <div style="padding-left:40px;padding-right:40px;display:none;min-height:130px" id="working">
        <h3><?php echo e(trans('texts.working')); ?>...</h3>
        <div class="progress progress-striped active">
          <div class="progress-bar"  role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div>
        </div>
      </div>

      <div style="background-color: #fff; padding-right:20px;padding-left:20px; display:none" id="signUpSuccessDiv">
        <br/>
        <h3><?php echo e(trans('texts.success')); ?></h3>
        <?php if(Utils::isNinja()): ?>
          <?php echo e(trans('texts.success_message')); ?>

        <?php endif; ?>
        <br/>&nbsp;
      </div>

      <div class="modal-footer" id="signUpFooter" style="margin-top: 0px">
        <button type="button" class="btn btn-default" id="closeSignUpButton" data-dismiss="modal"><?php echo e(trans('texts.close')); ?> <i class="glyphicon glyphicon-remove-circle"></i></button>
        <button type="button" class="btn btn-primary" id="saveSignUpButton" onclick="validateServerSignUp()" disabled><?php echo e(trans('texts.save')); ?> <i class="glyphicon glyphicon-floppy-disk"></i></button>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="myModalLabel"><?php echo e(trans('texts.logout')); ?></h4>
      </div>

      <div class="container">
        <h3><?php echo e(trans('texts.are_you_sure')); ?></h3>
        <p><?php echo e(trans('texts.erase_data')); ?></p>
      </div>

      <div class="modal-footer" id="signUpFooter">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo e(trans('texts.cancel')); ?></button>
        <button type="button" class="btn btn-primary" onclick="logout(true)"><?php echo e(trans('texts.logout')); ?></button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

</div>

<p>&nbsp;</p>


<?php $__env->stopSection(); ?>

<?php echo $__env->make('master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>