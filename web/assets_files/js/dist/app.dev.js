"use strict";

$(document).ready(function () {// $('body').hide()
});
$(document).ready(function () {
  $(".next").click(function () {
    $(".pagination").find(".pageNumber.active").next().addClass("active");
    $(".pagination").find(".pageNumber.active").prev().removeClass("active");
  });
  $(".prev").click(function () {
    $(".pagination").find(".pageNumber.active").prev().addClass("active");
    $(".pagination").find(".pageNumber.active").next().removeClass("active");
  });
});
var swiper = new Swiper(".mySwiper", {
  slidesPerView: 1,
  spaceBetween: 30,
  loop: true,
  pagination: {
    el: ".swiper-pagination",
    clickable: true
  },
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev"
  }
});
document.querySelector(".increment__text").innerText = "aad"