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

    console.log("Sending AJAX request with data:", data);

    $.ajax({
      url: wpCleanAdmin.ajax_url,
      type: "POST",
      data: data,
      success: function (response) {
        console.log("AJAX success response:", response);
        if (response.success) {
          updateProgress(response.data.progress, response.data.message);
          if (response.data.progress < 100) {
            processDelete();
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
        console.log("Response text:", jqXHR.responseText);
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
    console.log("Starting database optimization");
    $.ajax({
      url: wpCleanAdmin.ajax_url,
      type: "POST",
      data: {
        action: "wp_clean_optimize_database",
        nonce: wpCleanAdmin.nonce,
      },
      success: function (response) {
        console.log("Database optimization response:", response);
        if (response.success) {
          $("#wp-clean-progress-text").html(response.data);
        } else {
          console.error("Error in database optimization:", response.data);
          alert(response.data || wpCleanAdmin.ajax_error);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Database optimization error:", textStatus, errorThrown);
        console.log("Response text:", jqXHR.responseText);
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

  function exportData() {
    var data = $("#wp-clean-form").serialize();
    data += "&action=wp_clean_export_data&nonce=" + wpCleanAdmin.nonce;

    console.log("Sending export AJAX request with data:", data);

    $.ajax({
      url: wpCleanAdmin.ajax_url,
      type: "POST",
      data: data,
      success: function (response) {
        console.log("Export AJAX success response:", response);
        if (response.success) {
          window.location.href = response.data.download_url;
        } else {
          console.error("Error in export AJAX response:", response.data);
          alert(response.data || wpCleanAdmin.ajax_error);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Export AJAX error:", textStatus, errorThrown);
        console.log("Response text:", jqXHR.responseText);
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
