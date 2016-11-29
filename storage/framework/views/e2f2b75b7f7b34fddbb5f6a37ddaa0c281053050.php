<!DOCTYPE html>
<html lang="<?php echo e(App::getLocale()); ?>">
<head>
    <?php if(isset($account) && $account instanceof \App\Models\Account && $account->hasFeature(FEATURE_WHITE_LABEL)): ?>
        <title><?php echo e(trans('texts.client_portal')); ?></title>
    <?php else: ?>
        <title><?php echo e(isset($title) ? ($title . ' | EZInvoice') : ('EZInvoice | ' . trans('texts.app_title'))); ?></title>
        <meta name="description" content="<?php echo e(isset($description) ? $description : trans('texts.app_description')); ?>"/>
        <link href="<?php echo e(asset('favicon-v2.png')); ?>" rel="shortcut icon" type="image/png">
    <?php endif; ?>

<!-- Source: https://github.com/invoiceninja/invoiceninja -->
<!-- Version: <?php echo e(NINJA_VERSION); ?> -->

    <meta charset="utf-8">
    <meta property="og:site_name" content="EZInvoice"/>
    <meta property="og:url" content="<?php echo e(SITE_URL); ?>"/>
    <meta property="og:title" content="EZInvoice"/>
    <meta property="og:image" content="<?php echo e(SITE_URL); ?>/images/round_logo.png"/>
    <meta property="og:description" content="Simple, EZ Invoicing."/>

    <!-- http://realfavicongenerator.net -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(url('apple-touch-icon.png')); ?>">
    <link rel="icon" type="image/png" href="<?php echo e(url('favicon-32x32.png')); ?>" sizes="32x32">
    <link rel="icon" type="image/png" href="<?php echo e(url('favicon-16x16.png')); ?>" sizes="16x16">
    <link rel="manifest" href="<?php echo e(url('manifest.json')); ?>">
    <link rel="mask-icon" href="<?php echo e(url('safari-pinned-tab.svg')); ?>" color="#3bc65c">
    <link rel="shortcut icon" href="<?php echo e(url('favicon.ico')); ?>">
    <meta name="apple-mobile-web-app-title" content="EZInvoice">
    <meta name="application-name" content="EZInvoice">
    <meta name="theme-color" content="#ffffff">

    <!-- http://stackoverflow.com/questions/19012698/browser-cache-issues-in-laravel-4-application -->
    <meta http-equiv="cache-control" content="max-age=0"/>
    <meta http-equiv="cache-control" content="no-cache"/>
    <meta http-equiv="cache-control" content="no-store"/>
    <meta http-equiv="cache-control" content="must-revalidate"/>
    <meta http-equiv="expires" content="0"/>
    <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT"/>
    <meta http-equiv="pragma" content="no-cache"/>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="msapplication-config" content="none"/>
    <link rel="canonical" href="<?php echo e(NINJA_APP_URL); ?>/<?php echo e(Request::path()); ?>"/>

    <script src="<?php echo e(asset('built.js')); ?>?no_cache=<?php echo e(NINJA_VERSION); ?>" type="text/javascript"></script>

    <script type="text/javascript">
        var NINJA = NINJA || {};
        NINJA.fontSize = 9;
        NINJA.isRegistered = <?php echo e(\Utils::isRegistered() ? 'true' : 'false'); ?>;

        window.onerror = function (errorMsg, url, lineNumber, column, error) {
            if (errorMsg.indexOf('Script error.') > -1) {
                return;
            }

            try {
                // Use StackTraceJS to parse the error context
                if (error) {
                    var message = error.message ? error.message : error;
                    StackTrace.fromError(error).then(function (result) {
                        var gps = new StackTraceGPS();
                        gps.findFunctionName(result[0]).then(function (result) {
                            logError(errorMsg + ': ' + JSON.stringify(result));
                        });
                    });
                } else {
                    logError(errorMsg);
                }

                trackEvent('/error', errorMsg);
            } catch (err) {
            }

            return false;
        }

        function logError(message) {
            $.ajax({
                type: 'GET',
                url: '<?php echo e(URL::to('log_error')); ?>',
                data: 'error=' + encodeURIComponent(message) + '&url=' + encodeURIComponent(window.location)
            });
        }

        // http://t4t5.github.io/sweetalert/
        function sweetConfirm(success, text, title) {
            title = title || "<?php echo trans("texts.are_you_sure"); ?>";
            swal({
                //type: "warning",
                //confirmButtonColor: "#DD6B55",
                title: title,
                text: text,
                cancelButtonText: "<?php echo trans("texts.no"); ?>",
                confirmButtonText: "<?php echo trans("texts.yes"); ?>",
                showCancelButton: true,
                closeOnConfirm: false,
                allowOutsideClick: true,
            }, function() {
                success();
                swal.close();
            });
        }

        /* Set the defaults for DataTables initialisation */
        $.extend(true, $.fn.dataTable.defaults, {
            "bSortClasses": false,
            "sDom": "t<'row-fluid'<'span6'i><'span6'p>>l",
            "sPaginationType": "bootstrap",
            "bInfo": true,
            "oLanguage": {
                'sEmptyTable': "<?php echo e(trans('texts.empty_table')); ?>",
                'sLengthMenu': '_MENU_ <?php echo e(trans('texts.rows')); ?>',
                'sSearch': ''
            }
        });

        /* This causes problems with some languages. ie, fr_CA
         var appLocale = '<?php echo e(App::getLocale()); ?>';
         */

        <?php if(env('FACEBOOK_PIXEL')): ?>
        <!-- Facebook Pixel Code -->
        !function (f, b, e, v, n, t, s) {
            if (f.fbq)return;
            n = f.fbq = function () {
                n.callMethod ?
                        n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq)f._fbq = n;
            n.push = n;
            n.loaded = !0;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window,
                document, 'script', '//connect.facebook.net/en_US/fbevents.js');

        fbq('init', '<?php echo e(env('FACEBOOK_PIXEL')); ?>');
        fbq('track', "PageView");

        (function () {
            var _fbq = window._fbq || (window._fbq = []);
            if (!_fbq.loaded) {
                var fbds = document.createElement('script');
                fbds.async = true;
                fbds.src = '//connect.facebook.net/en_US/fbds.js';
                var s = document.getElementsByTagName('script')[0];
                s.parentNode.insertBefore(fbds, s);
                _fbq.loaded = true;
            }
        })();

        <?php else: ?>
        function fbq() {
            // do nothing
        }
        ;
        <?php endif; ?>

                window._fbq = window._fbq || [];

    </script>


    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->

    <?php echo $__env->yieldContent('head'); ?>

</head>

<body class="body">

<?php if(Utils::isNinjaProd() && isset($_ENV['TAG_MANAGER_KEY']) && $_ENV['TAG_MANAGER_KEY']): ?>
    <!-- Google Tag Manager -->
    <noscript>
        <iframe src="//www.googletagmanager.com/ns.html?id=<?php echo e($_ENV['TAG_MANAGER_KEY']); ?>"
                height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
    <script>(function (w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(), event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                    j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src =
                    '//www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', '<?php echo e($_ENV['TAG_MANAGER_KEY']); ?>');</script>
    <!-- End Google Tag Manager -->

    <script>
        function trackEvent(category, action) {
        }
    </script>
<?php elseif(Utils::isNinjaProd() && isset($_ENV['ANALYTICS_KEY']) && $_ENV['ANALYTICS_KEY']): ?>
    <script>
        (function (i, s, o, g, r, a, m) {
            i['GoogleAnalyticsObject'] = r;
            i[r] = i[r] || function () {
                        (i[r].q = i[r].q || []).push(arguments)
                    }, i[r].l = 1 * new Date();
            a = s.createElement(o),
                    m = s.getElementsByTagName(o)[0];
            a.async = 1;
            a.src = g;
            m.parentNode.insertBefore(a, m)
        })(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

        ga('create', '<?php echo e($_ENV['ANALYTICS_KEY']); ?>', 'auto');
        ga('send', 'pageview');

        function trackEvent(category, action) {
            ga('send', 'event', category, action, this.src);
        }
    </script>
<?php else: ?>
    <script>
        function trackEvent(category, action) {
        }
    </script>
<?php endif; ?>

<?php echo $__env->yieldContent('body'); ?>

<script type="text/javascript">
    NINJA.formIsChanged = <?php echo e(isset($formIsChanged) && $formIsChanged ? 'true' : 'false'); ?>;

    $(function () {
        $('form.warn-on-exit input, form.warn-on-exit textarea, form.warn-on-exit select').change(function () {
            NINJA.formIsChanged = true;
        });

        <?php if(Session::has('trackEventCategory') && Session::has('trackEventAction')): ?>
            <?php if(Session::get('trackEventAction') === '/buy_pro_plan'): ?>
                window._fbq.push(['track', '<?php echo e(env('FACEBOOK_PIXEL_BUY_PRO')); ?>', {
            'value': '<?php echo e(session('trackEventAmount')); ?>',
            'currency': 'USD'
        }]);
        <?php endif; ?>
        <?php endif; ?>

        <?php if(Session::has('onReady')): ?>
        <?php echo e(Session::get('onReady')); ?>

        <?php endif; ?>
    });
    $('form').submit(function () {
        NINJA.formIsChanged = false;
    });
    $(window).on('beforeunload', function () {
        if (NINJA.formIsChanged) {
            return "<?php echo e(trans('texts.unsaved_changes')); ?>";
        } else {
            return undefined;
        }
    });
    function openUrl(url, track) {
        trackEvent('/view_link', track ? track : url);
        window.open(url, '_blank');
    }
</script>

</body>

</html>
