jQuery(document).ready(function ($) {
  // Initialize tooltips
  $(".tooltip").tooltipster();

  let startTime;
  let deletionLog = [];

  $("#wp-clean-form").on("submit", function (e) {
    e.preventDefault();
    showConfirmation();
  });

  $("#wp-clean-confirm").on("click", function () {
    $("#wp-clean-confirmation").hide();
    startDeletion();
  });

  $("#wp-clean-cancel").on("click", function () {
    $("#wp-clean-confirmation").hide();
  });

  $("#wp-clean-download-log").on("click", function () {
    downloadLog();
  });

  function showConfirmation() {
    const summary = [];
    $("input[type=checkbox]:checked").each(function () {
      summary.push($(this).parent().text().trim());
    });

    $("#wp-clean-summary").html(
      summary.map((item) => `<li>${item}</li>`).join("")
    );
    $("#wp-clean-confirmation").show();
  }

  function startDeletion() {
    $("#wp-clean-progress").show();
    startTime = new Date();
    processDelete();
  }

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
          logDeletion(response.data.message);
          if (!response.data.is_complete) {
            processDelete(response.data.offset);
          } else {
            finalizeDeletion();
          }
        } else {
          handleError(response.data || wpCleanAdmin.ajax_error);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        handleError(
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
    $("#wp-clean-progress-bar-inner").css("width", progress + "%");
    $("#wp-clean-progress-text").html(message);
    updateEstimate(progress);
  }

  function updateEstimate(progress) {
    const elapsedTime = (new Date() - startTime) / 1000;
    const estimatedTotalTime = (elapsedTime / progress) * 100;
    const remainingTime = estimatedTotalTime - elapsedTime;
    const minutes = Math.floor(remainingTime / 60);
    const seconds = Math.floor(remainingTime % 60);
    $("#wp-clean-estimate").html(
      `Estimated time remaining: ${minutes}m ${seconds}s`
    );
  }

  function logDeletion(message) {
    deletionLog.push(message);
    $("#wp-clean-log-content").val(deletionLog.join("\n"));
  }

  function finalizeDeletion() {
    $("#wp-clean-progress-text").html(wpCleanAdmin.deletion_complete);
    $("#wp-clean-estimate").html("");
    $("#wp-clean-log").show();
    optimizeDatabase();
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
          logDeletion("Database optimization: " + response.data);
        } else {
          handleError("Error in database optimization: " + response.data);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        handleError(
          "Database optimization error: " + textStatus + " - " + errorThrown
        );
      },
    });
  }

  function handleError(errorMessage) {
    console.error(errorMessage);
    alert(errorMessage);
    logDeletion("Error: " + errorMessage);
  }

  function downloadLog() {
    const blob = new Blob([deletionLog.join("\n")], {
      type: "text/plain;charset=utf-8",
    });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "wp-clean-deletion-log.txt";
    link.click();
  }
});
