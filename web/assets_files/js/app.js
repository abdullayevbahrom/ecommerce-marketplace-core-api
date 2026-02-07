var loadFile = function (event) {
  var output = document.getElementById("output");
  output.src = URL.createObjectURL(event.target.files[0]);
  console.log(output.src);
  output.onload = function () {
    URL.revokeObjectURL(output.src);
  };
};
var loadFile2 = function (event) {
  var output = document.getElementById("output1");
  output.src = URL.createObjectURL(event.target.files[0]);
  output.onload = function () {
    URL.revokeObjectURL(output.src);
  };
};

var list = document.getElementById('demos');
var loadImage = function (event) {
  var firstname = URL.createObjectURL(event.target.files[0]);
  var entry = document.createElement('img')
  var div = document.createElement('div')
  div.classList.add('photo__col')
  var btn = document.createElement("button");
  btn.className = "fas fa-times photo__delete";
  btn.addEventListener("click", function(e) {
    e.target.parentNode.remove();
  });
  entry.src = firstname
  list.appendChild(div);
  div.appendChild(entry)
  div.appendChild(btn)
};
function myFunction() {
  var x = document.getElementById("myDIV");
  if (x.style.display === "none") {
    x.style.display = "block";
  } else {
    x.style.display = "none";
  }
}
function closeMenu() {
  var close = document.getElementById("Menu");
  close.style.display = "none";
}
function openAuth() {
  var auth = document.getElementById("auth");
  var reg = document.getElementById("reg");
  auth.style.display = "flex";
  reg.style.display = "none";
}
function openMenu() {
  var close = document.getElementById("Menu");
  close.style.display = "block";
}
function dropdownFunction() {
  var x = document.getElementById("dropdown");
  if (x.style.display === "none") {
    x.style.display = "block";
  } else {
    x.style.display = "none";
  }
}
function modal1() {
  var x = document.getElementById("modal1");
  x.style.display = "block";
}
function modal2() {
  var x = document.getElementById("modal2");

  x.style.display = "block";
}
function modal3() {
  var x = document.getElementById("modal3");

  x.style.display = "block";
}
function cancel1() {
  var x = document.getElementById("modal1");
  x.style.display = "none";
}
function cancel2() {
  var x = document.getElementById("modal2");

  x.style.display = "none";
}
function cancel3() {
  var x = document.getElementById("modal3");

  x.style.display = "none";
}
var images_modal1 = document.getElementById("modal1");
var images_modal2 = document.getElementById("modal2");
var images_modal3 = document.getElementById("modal3");

// When the user clicks anywhere outside of the modal, close it

window.addEventListener("click", function (event) {
  if (event.target == images_modal1) {
    images_modal1.style.display = "none";
  }
});
window.addEventListener("click", function (event) {
  if (event.target == images_modal2) {
    images_modal2.style.display = "none";
  }
});
window.addEventListener("click", function (event) {
  if (event.target == images_modal3) {
    images_modal3.style.display = "none";
  }
});
var images_modal = document.getElementById("dropdown");
// When the user clicks anywhere outside of the modal, close it

window.addEventListener("click", function (event) {
  if (event.target == images_modal) {
    images_modal.style.display = "none";
  }
});
$(document).ready(function () {
  // $('body').hide()
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
    clickable: true,
  },
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },
});

$(".radioContainer").on("click", function (e) {
  e.preventDefault();
  $(".radioContainer").removeClass("active");
  $(this).addClass("active");
});
new WOW().init();
// Google map
function initMap() {
  // The location of Uluru
  const uluru = { lat: -25.344, lng: 131.036 };
  // The map, centered at Uluru
  const map = new google.maps.Map(document.getElementById("map"), {
    zoom: 4,
    center: uluru,
  });
  // The marker, positioned at Uluru
  const marker = new google.maps.Marker({
    position: uluru,
    map: map,
  });
}


