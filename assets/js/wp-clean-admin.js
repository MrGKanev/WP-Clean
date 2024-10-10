jQuery(document).ready(function ($) {
  $("#wp-clean-form").on("submit", function (e) {
    e.preventDefault();

    if ($("#wp-clean-submit").is(":focus")) {
      if (!confirm(wpCleanAdmin.confirm_deletion)) {
        return;
      }
      processDelete();
    }
  });

  function processDelete(offset = 0) {
    var data = $("#wp-clean-form").serialize();
    data +=
      "&action=wp_clean_process_deletion&nonce=" +
      wpCleanAdmin.nonce +
      "&offset=" +
      offset;

    $.ajax({
      url: wpCleanAdmin.ajax_url,
      type: "POST",
      data: data,
      success: function (response) {
        if (response.success) {
          updateProgress(response.data.progress, response.data.message);
          if (!response.data.is_complete) {
            processDelete(response.data.offset);
          } else {
            $("#wp-clean-progress-text").html(wpCleanAdmin.deletion_complete);
            optimizeDatabase();
          }
        } else {
          console.error("Error in AJAX response:", response.data);
          alert(response.data || wpCleanAdmin.ajax_error);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("AJAX error:", textStatus, errorThrown);
        alert(
          wpCleanAdmin.ajax_error +
            "\n\nDetails: " +
            textStatus +
            " - " +
            errorThrown
        );
      },
    });
  }

  function updateProgress(progress, message) {
    $("#wp-clean-progress").show();
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
          console.error("Error in database optimization:", response.data);
          alert(response.data || wpCleanAdmin.ajax_error);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Database optimization error:", textStatus, errorThrown);
        alert(
          wpCleanAdmin.ajax_error +
            "\n\nDetails: " +
            textStatus +
            " - " +
            errorThrown
        );
      },
    });
  }
});
