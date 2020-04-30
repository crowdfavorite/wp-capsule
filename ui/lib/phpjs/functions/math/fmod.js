function fmod (x, y) {
    // http://kevin.vanzonneveld.net
    // +   original by: Onno Marsman
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // *     example 1: fmod(5.7, 1.3);
    // *     returns 1: 0.5
    var tmp, tmp2, p = 0,
        pY = 0,
        l = 0.0,
        l2 = 0.0;

    tmp = x.toExponential().match(/^.\.?(.*)e(.+)$/);
    p = parseInt(tmp[2], 10) - (tmp[1] + '').length;
    tmp = y.toExponential().match(/^.\.?(.*)e(.+)$/);
    pY = parseInt(tmp[2], 10) - (tmp[1] + '').length;

    if (pY > p) {
        p = pY;
    }

    tmp2 = (x % y);

    if (p < -100 || p > 20) {
        // toFixed will give an out of bound error so we fix it like this:
        l = Math.round(Math.log(tmp2) / Math.log(10));
        l2 = Math.pow(10, l);

        return (tmp2 / l2).toFixed(l - p) * l2;
    } else {
        return parseFloat(tmp2.toFixed(-p));
    }
}
