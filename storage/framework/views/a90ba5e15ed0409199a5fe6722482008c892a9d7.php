<?php $__env->startSection('head'); ?>

<link href="<?php echo e(asset('css/bootstrap.min.css')); ?>" rel="stylesheet" type="text/css"/>
<link href="<?php echo e(asset('css/style.min.css')); ?>" rel="stylesheet" type="text/css"/>

<style type="text/css">
    body {
        padding-top: 40px;
        padding-bottom: 40px;
    }
    .modal-header {
        border-top-left-radius: 3px;
        border-top-right-radius: 3px;
    }
    .modal-header h4 {
        margin:0;
    }
    .modal-header img {
        float: left;
        margin-right: 20px;
    }
    .form-signin {
        max-width: 400px;
        margin: 0 auto;
        background: #fff;
    }
    p.link a {
        font-size: 11px;
    }
    .form-signin .inner {
        padding: 20px;
        border-bottom-right-radius: 3px;
        border-bottom-left-radius: 3px;
        border-left: 1px solid #ddd;
        border-right: 1px solid #ddd;
        border-bottom: 1px solid #ddd;
    }
    .form-signin .checkbox {
        font-weight: normal;
    }
    .form-signin .form-control {
        margin-bottom: 17px !important;
    }
    .form-signin .form-control:focus {
        z-index: 2;
    }

    .modal-header a:link,
    .modal-header a:visited,
    .modal-header a:hover,
    .modal-header a:active {
        text-decoration: none;
        color: white;
    }

</style>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('body'); ?>
<div class="container">

    <?php echo $__env->make('partials.warn_session', ['redirectTo' => '/login'], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

    <?php echo Former::open('login')
            ->rules(['email' => 'required|email', 'password' => 'required'])
            ->addClass('form-signin'); ?>

    <?php echo e(Former::populateField('remember', 'true')); ?>


    <div class="modal-header">
        <a href="<?php echo e(NINJA_WEB_URL); ?>" target="_blank">
            <!--<img src="<?php echo e(asset('images/icon-login.png')); ?>" />//-->
            <h4>EZInvoice | <?php echo e(trans('texts.account_login')); ?></h4>
        </a>
    </div>
        <div class="inner">
            <p>
                <?php echo Former::text('email')->placeholder(trans('texts.email_address'))->raw(); ?>

                <?php echo Former::password('password')->placeholder(trans('texts.password'))->raw(); ?>

                <?php echo Former::hidden('remember')->raw(); ?>

            </p>

            <p><?php echo Button::success(trans('texts.login'))
                    ->withAttributes(['id' => 'loginButton'])
                    ->large()->submit()->block(); ?></p>

            <?php if(Input::get('new_company') && Utils::allowNewAccounts()): ?>
                <?php echo Former::hidden('link_accounts')->value('true'); ?>

                <center><p>- <?php echo e(trans('texts.or')); ?> -</p></center>
                <p><?php echo Button::primary(trans('texts.new_company'))->asLinkTo(URL::to('/invoice_now?new_company=true&sign_up=true'))->large()->submit()->block(); ?></p><br/>
            <?php elseif(Utils::isOAuthEnabled()): ?>
                <center><p>- <?php echo e(trans('texts.or')); ?> -</p></center>
                <div class="row">
                <?php foreach(App\Services\AuthService::$providers as $provider): ?>
                    <div class="col-md-6">
                        <a href="<?php echo e(URL::to('auth/' . $provider)); ?>" class="btn btn-primary btn-block social-login-button" id="<?php echo e(strtolower($provider)); ?>LoginButton">
                            <i class="fa fa-<?php echo e(strtolower($provider)); ?>"></i> &nbsp;
                            <?php echo e($provider); ?>

                        </a><br/>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p class="link">
                <?php echo link_to('/recover_password', trans('texts.recover_password')); ?>

                <!--<?php echo link_to(NINJA_WEB_URL.'/knowledgebase/', trans('texts.knowledge_base'), ['target' => '_blank', 'class' => 'pull-right']); ?>//-->
            </p>

            <?php if(count($errors->all())): ?>
                <div class="alert alert-danger">
                    <?php foreach($errors->all() as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if(Session::has('warning')): ?>
            <div class="alert alert-warning"><?php echo Session::get('warning'); ?></div>
            <?php endif; ?>

            <?php if(Session::has('message')): ?>
            <div class="alert alert-info"><?php echo Session::get('message'); ?></div>
            <?php endif; ?>

            <?php if(Session::has('error')): ?>
            <div class="alert alert-danger"><li><?php echo Session::get('error'); ?></li></div>
            <?php endif; ?>

        </div>

        <?php echo Former::close(); ?>


        <p/>
        <center>
            <!--
            <div id="fb-root"></div>
            <script>(function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s); js.id = id;
                js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=635126583203143";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));</script>

            <div class="fb-follow" data-href="https://www.facebook.com/invoiceninja" data-colorscheme="light" data-layout="button" data-show-faces="false"></div>&nbsp;&nbsp;

            <a href="https://twitter.com/invoiceninja" class="twitter-follow-button" data-show-count="false" data-related="hillelcoren" data-size="medium">Follow @invoiceninja</a>
            <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>

            <iframe src="https://ghbtns.com/github-btn.html?user=hillelcoren&repo=invoice-ninja&type=star&count=false" frameborder="0" scrolling="0" width="50px" height="20px"></iframe>
            -->

            <p>&nbsp;</p>
            <p>&nbsp;</p>

            <!--
            <iframe allowTransparency="true" frameborder="0" scrolling="no" src="https://bitnami.com/product/invoice-ninja/widget" style="border:none;width:230px; height:100px;"></iframe>
            -->

        </center>

    </div>


    <script type="text/javascript">
        $(function() {
            if ($('#email').val()) {
                $('#password').focus();
            } else {
                $('#email').focus();
            }

            /*
            var authProvider = localStorage.getItem('auth_provider');
            if (authProvider) {
                $('#' + authProvider + 'LoginButton').removeClass('btn-primary').addClass('btn-success');
            }
            */
        })

    </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>