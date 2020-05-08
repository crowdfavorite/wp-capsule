require.config({
    baseUrl: requirejsL10n.capsule,
    enforceDefine: true,
    paths: {
        "cf": requirejsL10n.capsule,
        "ace": requirejsL10n.ace + "/lib/ace"
    },
    urlArgs: "ver=" + requirejsL10n.cachebust
});

// Fake jQuery definition to avoid loading jQuery
// twice or other similar inconveniences
if (!require.defined("jquery")) {
    define('jquery', function wpjquery() {
        if (!wpjquery.jQuery) {
            wpjquery.jQuery = window.jQuery;
        }
        return wpjquery.jQuery;
    });
}