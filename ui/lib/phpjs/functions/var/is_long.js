function is_long (mixed_var) {
    // http://kevin.vanzonneveld.net
    // +   original by: Paulo Freitas
    //  -   depends on: is_float
    // %        note 1: 1.0 is simplified to 1 before it can be accessed by the function, this makes
    // %        note 1: it different from the PHP implementation. We can't fix this unfortunately.
    // *     example 1: is_long(186.31);
    // *     returns 1: true
    return this.is_float(mixed_var);
}
