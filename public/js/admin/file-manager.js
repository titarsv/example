/*=========================================================================================
    File Name: app-email.js
    Description: app-email Javascripts
    ----------------------------------------------------------------------------------------
    Item Name: Frest HTML Admin Template
   Version: 1.0
    Author: PIXINVENT
    Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

$(function () {
  "use strict";
  // variables
  var appContent = $(".app-content"),
    appContentOverlay = $(".app-file-overlay"),
    sideBarLeft = $(".sidebar-left"),
    app_file_application = $(".file-manager-application"),
    sideBarInfo = $(".app-file-sidebar-info"),
    app_file_content = $(".app-file-content"),
    app_file_sidebar_left = $(".app-file-sidebar-left"),
    $primary = "#5A8DEE";

  // To add Perfect Scrollbar
  // ---------------------------

  // App-File Content Area
  if (app_file_content.length > 0) {
    var users_list = new PerfectScrollbar(".app-file-content");
  }

  // App File Left Sidebar
  if (app_file_sidebar_left.length > 0) {
    var app_file_sidebar_content = new PerfectScrollbar(".app-file-sidebar-content");
  }

  // Edit File Sidebar - Right Side
  if ($(".app-file-sidebar-info").length > 0) {
    var edit_file_sidebar = new PerfectScrollbar(".app-file-sidebar-info");
  }


  // Sidebar visiblility and content area overlay
  // ----------------------------------------------
  $(document).on('click', '.menu-toggle, .close-icon, .app-file-overlay', function (e) {
    $(".sidebar-left").removeClass('show');
    $(".app-file-overlay").removeClass('show');
    $(".app-file-sidebar-info").removeClass('show');
  });

  // On click of "app-file-info" class from file-content-area, visible edit sidebar and hide left sidebar
  // $(document).on('click', '.app-file-info', function () {
  //   $(".app-file-sidebar-info").addClass('show');
  //   $(".app-file-overlay").addClass('show');
  // });

  // Sidebar menu close button on click remove show class form both sidebar-left and App content overlay
  $(document).on('click', ".app-file-sidebar-close", function () {
    $(".sidebar-left").removeClass('show');
    $(".app-file-overlay").removeClass('show');
  });

  // on click of Sidebar-toggle, add class show to content overlay and toggle class to app sidebar left
  $(document).on('click', '.sidebar-toggle', function (e) {
      e.stopPropagation();
      $(".sidebar-left").addClass('show');
      $(".app-file-overlay").addClass('show');
  });

  // Add class active on click of sidebar list folder and label
  var $active_label = $(".app-file-sidebar-content .list-group-messages a,.list-group-labels a");
  $($active_label).on('click', function () {
    var $this = $(this);
      $active_label.removeClass('active');
      $this.addClass("active");
      // livicon change color on active state
      $this.find(".livicon-evo").updateLiviconEvo({
        strokeColor: $primary
      });
      $active_label.not(".active").find(".livicon-evo").updateLiviconEvo({
        strokeColor: '#475f7b'
      });
  });

  // On screen Resize JS
  // -----------------------------------
  $(window).on("resize", function () {
    // remove show classes from sidebar and overlay if size is > 768
    if ($(window).width() > 768) {
      if (appContentOverlay.hasClass('show')) {
        $(".sidebar-left").removeClass('show');
        $(".app-file-overlay").removeClass('show');
        $(".app-file-sidebar-info").removeClass('show');
      }
    }
  });
});
