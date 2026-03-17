<!DOCTYPE html>
<html lang="en">
<head>
  <title>E-Academy Email Template 01</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@100;200;300;400;500;600;700;800;900&family=Cinzel:wght@400;500;600;700;800;900&family=K2D:wght@100;200;300;400;500;600;700;800&display=swap" rel="stylesheet">

  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@100;200;300;400;500;600;700;800;900&family=K2D:wght@100;200;300;400;500;600;700;800&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@100;200;300;400;500;600;700;800;900&family=Great+Vibes&family=K2D:wght@100;200;300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    h1,h1,h3,h4,h5,h6{
        padding: 0;
        margin: 0;
    }
    .ea_email_wrapper {
        background-image: url(<?=base_url();?>assets/images/email-template02.jpg);
        background-repeat: no-repeat;
        width: 100%;
        height: 870px;
        background-position: center;
        max-width: 1205px;
        margin: 0 auto;
        position:relative;
        overflow:hidden;
        z-index:-1;
    }
    .ea_email_logo {
        text-align: center;
        padding: 98px 0px 0;
        width: 100px;
        max-width: 100px;
        margin: 0 auto;
        height: 130px;
    }
 .ea_email_wrapper h5 {
    font-size: 90px;
    color: #fcb945;
    font-weight: 300;
    font-family: "Cinzel";
    text-align: center;
    padding: 10px 0 0px 0;
    position: absolute;
    top: 100px;
    left: 200px;
}
   .ea_email_wrapper h4 {
    font-size: 25px;
    color: #929497;
    font-weight: 400;
    font-family: "Cinzel";
    /*text-align: center;*/
    padding: 0 0 10px 0;
    position: absolute;
    top: 220px;
     left: 100px;
     right:auto;
}
  .ea_email_wrapper h3 {
    font-size: 17px;
    color: #929497;
    font-weight: 400;
    font-family: "Cinzel";
    text-align: center;
    padding: 10px 0 10px 0;
    position: absolute;
    left: 300px;
    top: 290px;
}
 .ea_email_wrapper h2 {
    font-size: 67px;
    color: #fcb945;
    font-weight: 400;
    font-family: "Great Vibes";
    text-align: center;
    padding: 15px 0 0 0;
    position: absolute;
    left: 0;
    right: 0;
    top: 330px;
}
    .ea_email_wrapper p {
        font-size: 18px;
        color: #929497;
        font-weight: 400;
        font-style: italic;
        font-family: "Raleway";
        text-align: center;
        width: 100%;
        max-width: 700px;
        margin: 0 auto;
        padding: 20px 0px 30px 0px;
          position: absolute;
    left: 0;
    right: 0;
    top: 450px;
    }
    .ea_email_year > h2 {
        font-size: 40px;
        color: #fcb945;
        font-weight: 900;
        font-family: "Great Vibes";
        text-align: center;
        padding: 50px 0 0 0;
        position: absolute;
        left: 0;
        right: 0;
        bottom: 185px;
        top: auto;
    }
    .ea_email_date > h2 {
        font-size: 30px;
        color: #fcb945;
        font-weight: 400;
        font-family: "Great Vibes";
        text-align: left;
        padding: 40px 0 0px 175px;
        position: absolute;
        left: 0;
        right: 0;
        top: auto;
        bottom: 90px;
    }
  </style>
</head>
<body>
<div class="ea_email_wrapper">
 <?php echo '<img  class="edu_certifiate_Bgwrapper certificate_logo certificate_img_css" src="data:image/jpg;base64,'.base64_encode(file_get_contents(base_url().'assets/images/email-template02.jpg')).'"  alt="Logo">';?>
   <div class="ea_email_logo">
        <?php echo '<img  class="edu_certifiate_Bgwrapper" src="data:image/jpg;base64,'.base64_encode(file_get_contents('https://kamleshyadav.in/Eacademy_update/uploads/site_data/favicon8.png')).'" class="certificate_logo" alt="Logo">';?>          
       <!--<img src="https://kamleshyadav.in/Eacademy_update/uploads/site_data/favicon8.png"></div>-->
   <h5>CERTIFICATE</h5>
   <h4>OF APPRECIATION</h4>
   <h3>THIS CERTIFICATE IS PROUDLY PRESENTED BY</h3>
   <h2>Simran Jadhav</h2>
   <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Et quidem saepe quaerimus verbum 
    Latinum par Graeco et quod idem valeat; Neque enim disputari sine reprehensione nec cum 
    iracundia aut pertinacia recte disputari potest. </p>
    <div class="ea_email_year"><h2>2023</h2></div>
    <div class="ea_email_date">
        <h2>25-04-2023</h2>
    </div>
</div>
</body>
</html>