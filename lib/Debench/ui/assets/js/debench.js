$(window).ready(function () {
  debenchPanelLast = debenchGetCookie("debench_panel");

  if (debenchPanelLast != "") {
    debenchTogglePannels();
  }

  $("debench ul li[data-target]").on("click", function () {
    $("debench ul li[data-target]").removeClass("active");
    $(this).addClass("active");

    let panel = $(this).data("target");
    if (panel == "debench-panel-toggle") {
      debenchTogglePannels();
    } else {
      $("debench ul.debench-main-block > li:not(:last-child)").hide();
      if (panel == debenchPanelLast) {
        $("." + panel).hide();
        $(this).removeClass("active");
        panel = "";
      } else {
        $("." + panel).show();
        $(this).addClass("active");
      }
      debenchPanelLast = panel;
    }

    let isVisible = $("debench .debench-main-panel").is(":visible");
    let panelToKeep = debenchPanelLast
      ? debenchPanelLast
      : "debench-panel-toggle";
    debenchSetCookie("debench_panel", isVisible ? panelToKeep : "");
  });

  $("debench .debench-counter[data-badge]").each(function () {
    item = $(this);
    if (parseInt(item.text()) != 0) {
      item.addClass(item.data("badge"));
    } else {
      item.removeClass(item.data("badge"));
    }
  });
});

function debenchTogglePannels() {
  $("debench .debench-main-panel").toggle();
  $("debench .debench-main-panel-minimal").toggle();
  if (debenchPanelLast != "") {
    $("debench ul.debench-main-block > li." + debenchPanelLast).toggle();
  }
}

function debenchGetCookie(key) {
  return $.cookie(key);
}

function debenchSetCookie(key, value) {
  $.cookie(key, value, 1, {
    expires: 10,
    path: "/",
  });
}
