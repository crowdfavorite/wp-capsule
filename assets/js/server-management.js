(function ($) {
  var CapsuleServerManagement = {
    init: function() {
      $('#wpbody-content').on('click', '.js-cap-edit-server', function(e) {
        var server_id = $(this).data('server_id');
        e.preventDefault();
        $('tr#js-server-item-'+server_id+' .js-cap-not-editable').hide();
        $('tr#js-server-item-'+server_id+' .js-cap-editable').show();
      });

      $('form#js-cap-servers').on('submit', function(e) {
        CapsuleServerManagement.capsuleServers(e);
      });

      $('#wpbody-content').on('click', '.js-cap-save-server', function(e) {
        CapsuleServerManagement.saveServer(e);
      });

      $('#wpbody-content').on('click', '.js-server-delete', function(e) {
        CapsuleServerManagement.deleteServer(e);
      });
    },

    process_server_errors: function ($tr, result, server_id, new_server) {
      var error_html = '';
      var err_msg = '';
      var $new_error_div;
      new_server == undefined ? new_server : false;
      CapsuleServerManagement.remove_input_errors($tr);

      if (!new_server) {
        $tr.fadeIn();
      }

      for (var key in result.errors) {
        $new_error_div = $('<div class="capsule-error js-cap-error-'+server_id+'" style="display:none;"></div>');
        error_html = result.errors[key].message;
        if (result.errors[key].type == 'url') {
          $('.js-cap-server-url', $tr).addClass('cap-input-error');
        }
        if (result.errors[key].type == 'credentials') {
          $('.js-cap-server-api-key', $tr).addClass('cap-input-error');
        }
        if (result.errors[key].type == 'name') {
          $('.js-cap-server-name', $tr).addClass('cap-input-error');
        }
        err_msg = '<b>' + CapsuleServerManagementSettings.errorPrefix + '</b>' + result.data.name + ' - ' + error_html;
        $new_error_div.html('<p>' + err_msg + '</p>')
          .appendTo('#cap-servers').fadeIn();
      }
    },

    reset_server_form: function ($form) {
      $('input[name="server_name"]', $form).val("");
      $('input[name="server_url"]', $form).val("");
      $('input[name="server_api_key"]', $form).val("");
    },

    remove_input_errors: function ($tr) {
      $('.js-cap-server-api-key', $tr).removeClass('cap-input-error');
      $('.js-cap-server-url', $tr).removeClass('cap-input-error');
      $('.js-cap-server-name', $tr).removeClass('cap-input-error');
    },

    capsuleServers: function (e) {
      var $form = $(e.target);
      var $spinner = $('.js-cap-add').siblings('.capsule-spinner');
      var $tr = $('tr#js-server-item-new');
      e.preventDefault();

      $form.find('input[name="capsule_client_action"]').val('add_server_ajax');
      $('.js-cap-error-new').hide();
      CapsuleServerManagement.remove_input_errors($tr);
      $spinner.show();

      $.post(
        ajaxurl,
        $form.serialize(),
        function(result) {
          $spinner.hide();
          if (result.result == 'success') {
            $(result.html).insertBefore("tr#js-server-item-new").hide().fadeIn();
            CapsuleServerManagement.reset_server_form($form);
          }
          else {
            CapsuleServerManagement.process_server_errors($tr, result, 'new', true);
          }
        },
        'json'
      );
    },

    saveServer: function(e) {
      var $button = $(e.target);
      var server_id = $button.data('server_id');
      var $form = $('form#js-cap-servers');
      var $tr = $('tr#js-server-item-'+server_id);
      var $spinner = $button.siblings('.capsule-spinner');

      e.preventDefault();
      $spinner.show();

      $.post(
        ajaxurl,
        {
          capsule_client_action : 'update_server_ajax',
          server_name : $('#js-server-name-'+server_id).val(),
          server_id : server_id,
          api_key : $('#js-server-api_key-'+server_id).val(),
          server_url : $('#js-server-url-'+server_id).val(),
          _server_nonce : $('#_server_nonce').val(),
          _wp_http_referer : $form.find('input[name="_wp_http_referer"]').val()
        },
        function(result) {
          $spinner.hide();
          if (result.result == 'success') {
            $('.js-cap-server-api-key', $tr).removeClass('cap-input-error');
            $('.js-cap-server-url', $tr).removeClass('cap-input-error');

            $('.js-static-server-api-'+server_id).html(result.data.api_key);
            $('.js-static-server-name-'+server_id).html(result.data.name);
            $('.js-static-server-url-'+server_id).html(result.data.url);
            $('.js-cap-error-'+server_id).remove();
            $tr.animate({opacity:0}, function() {
              $('.js-cap-not-editable', $tr).show();
              $('.js-cap-editable', $tr).hide();
              $tr.animate({opacity:1});
            });
          }
          else {
            $('.js-cap-error-'+server_id).fadeOut(function() {
              $(this).remove()
            });
            $tr.fadeOut(function() {
              CapsuleServerManagement.process_server_errors($tr, result, server_id, false);
            });
          }
        },
        'json'
      );
    },

    deleteServer: function(e) {
      var $link = $(e.target);
      var url = $link.attr('href');
      var $form = $('#js-cap-servers');
      var server_id = $link.data('server_id');
      e.preventDefault();
      $.get(
        url,
        { doing_ajax : true },
        function(data) {
          if (data.result == 'success') {
            $form.find('#js-server-item-'+server_id).fadeOut();
          }
          else {
            //nothing it hasn't been deleted
          }
        },
        'json'
      );
    }
  };

  $(document).ready(function() {
    CapsuleServerManagement.init();
  });
})(jQuery);
