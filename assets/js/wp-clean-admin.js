jQuery(document).ready(function ($) {
  $("#wp-clean-form").on("submit", function (e) {
    e.preventDefault();

    if ($("#wp-clean-submit").is(":focus")) {
      if (!confirm(wpCleanAdmin.confirm_deletion)) {
        return;
      }
      processDelete();
    } else if ($("#wp-clean-export").is(":focus")) {
      exportData();
    }
  });

  function processDelete() {
    var data = $("#wp-clean-form").serialize();
    data += "&action=wp_clean_process_deletion&nonce=" + wpCleanAdmin.nonce;

    $.ajax({
      url: wpCleanAdmin.ajax_url,
      type: "POST",
      data: data,
      success: function (response) {
        if (response.success) {
          updateProgress(response.data.progress, response.data.message);
          if (response.data.progress < 100) {
            processDelete();
          } else {
            $("#wp-clean-progress-text").html(wpCleanAdmin.deletion_complete);
            optimizeDatabase();
          }
        } else {
          alert(response.data);
        }
      },
      error: function () {
        alert(wpCleanAdmin.ajax_error);
      },
    });
  }

  function updateProgress(progress, message) {
    $("#wp-clean-progress-bar-inner").css("width", progress + "%");
    $("#wp-clean-progress-text").html(message);
  }

  function optimizeDatabase() {
    $.ajax({
      url: wpCleanAdmin.ajax_url,
      type: "POST",
      data: {
        action: "wp_clean_optimize_database",
        nonce: wpCleanAdmin.nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#wp-clean-progress-text").html(response.data);
        } else {
          alert(response.data);
        }
      },
    });
  }

  function exportData() {
    var data = $("#wp-clean-form").serialize();
    data += "&action=wp_clean_export_data&nonce=" + wpCleanAdmin.nonce;

    $.ajax({
      url: wpCleanAdmin.ajax_url,
      type: "POST",
      data: data,
      success: function (response) {
        if (response.success) {
          window.location.href = response.data.download_url;
        } else {
          alert(response.data);
        }
      },
    });
  }
});
