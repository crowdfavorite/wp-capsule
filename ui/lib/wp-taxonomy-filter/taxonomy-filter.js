(function($) {
  $(document).on('ready', function() {
    $('.cftf-tax-select, .cftf-author-select').chosen({
      allow_single_deselect: true,
      width: '18%'
    });
    $('.cftf-date').datepicker({
      dateFormat: 'yy-mm-dd'
    });
  });

  // Clean up the URLs
  $('form.cftf-filter').on('submit', function() {
    $(this).children(':input[value=""]').attr('disabled', 'disabled');
    return true;
  });
})(jQuery);
