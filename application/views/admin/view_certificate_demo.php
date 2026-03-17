<!DOCTYPE html>
<?php 
if($certificate_details[0]['template_id']==1){
    ?>
    <html <?php if($this->common->language_name=='arabic'){echo 'lang="ar" dir="rtl"';}else
        if($this->common->language_name=='french'){echo 'lang="fr" dir="ltr"';}else
        if($this->common->language_name=='english'){echo 'lang="en" dir="ltr"';} ?> >
    
        <head>
            <!-- Required meta tags -->
            <meta charset="utf-8">
            <!--<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">-->
            <link
                href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,900&display=swap"
                rel="stylesheet">
            <style>
                body {
                    background: #f7f7fb;
                    font-weight: 400;
                    font-size: 16px;
                    line-height: 26px;
                    color: #888888;
                    font-family: 'Poppins', sans-serif;
                    transform: scale(1);
                }
    
                .edu_certifiate_heading {
                    padding: 140px 0 0 181px;
                }
    
                .edu_certifiate_wrapper {
                    background: url("<?php echo base_url('assets/images/certificate_bg.png'); ?>");
                    width: 1203px;
                    height: 871px;
                    margin: auto;
                    position: relative;
    
                }
    
                .title1 {
                    font-size: 80px;
                    text-transform: uppercase;
                    color: #4d4a81;
                    margin-bottom: 37px;
                }
    
                .title2 {
                    font-size: 30px;
                    font-weight: 400;
                    text-transform: uppercase;
                    color: #f0c586;
                    position: relative;
                    display: inline-block;
                    padding: 0;
                    margin: 0;
                    padding-left: 264px;
                }
    
                .certifiate_prodly_wrap {
                    text-align: center;
                }
    
                .certificate_Proudly {
                    font-size: 26px;
                    color: #4d4784;
                    font-weight: 500;
                    margin-top: 40px;
                }
    
                .name_title {
                    font-size: 45px;
                    font-weight: 700;
                    text-transform: capitalize;
                    text-align: center;
                    color: #242424;
                    margin-bottom: 33px;
                    margin-top: 100px;
                }
    
                .certificate_discrip {
                    font-size: 18px;
                    text-align: center;
                    padding: 0 12%;
                    line-height: 28px;
                }
    
                .certifiate_main_logo {
                    position: absolute;
                    top: 140px;
                    right: 132px;
                    width: 180px;
                    height: 180px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    border-radius: 100%;
                    overflow: hidden;
                }
    
                .certificate_logo {
                    width: 70%;
                }
    
                .certifiate_date {
                    position: absolute;
                    bottom: 160px;
                    left: 233px;
                    color: #4d4784;
                    font-weight: 500;
                    font-size: 20px;
                }
    
                .certifiate_sign {
                    position: absolute;
                    right: 171px;
                    bottom: 173px;
                    width: 100px;
                    height: 50px;
                }
    
                .certifiate_sign>img {
                    width: 100px;
                    height: 50px;
                    object-fit: contain;
                }
    
                .title2:after {
                    content: "";
                    position: absolute;
                    left: -164px;
                    width: 420px;
                    top: 0;
                    height: 23px;
                    background: #fcd195;
                    bottom: 0;
                    margin: auto;
                }
    
                .certificate_btn {
                    height: 45px;
                    line-height: 45px;
                    text-align: center;
                    padding: 0 15px;
                    background-color: #4d4a81;
                    display: inline-block;
                    font-size: 14px;
                    color: #ffffff;
                    font-weight: 600;
                    border-radius: 6px;
                    border: none;
                    cursor: pointer;
                    outline: none;
                    min-width: 170px;
                    position: relative;
                    box-shadow: none;
                    text-transform: uppercase;
                }
    
                .certificate_btn_wrap {
                    text-align: center;
                }
    
                /*for rtl css*/
                .rtl .edu_certifiate_heading {
                    padding: 140px 180px 0 0;
                }
    
                .rtl .certifiate_main_logo {
                    right: auto;
                    left: 132px;
                }
    
                .rtl .title2 {
                    padding-left: 0;
                    padding-right: 264px;
                }
    
                .rtl .title2:after {
                    left: auto;
                    right: -162px;
                }
    
                .rtl .certifiate_date {
                    left: auto;
                    right: 233px;
                }
    
                .rtl .certifiate_sign {
                    right: auto;
                    left: 171px;
                }
    
                .rtl .edu_certifiate_wrapper {
                    background: url("<?php echo base_url('assets/images/rtl/rtlcertificate_bg.png'); ?>");
                }
            </style>
        </head>
    
        <body class="form <?php if($this->common->language_name=='arabic'){ echo 'rtl';}?>">
    
            <div class="conatiner_certificate">
                <div class="edu_certifiate_wrapper">
                    <div class="edu_certifiate_inner">
                        <div class="edu_certifiate_heading">
                            <h1 class="title1">
                                <?php echo $certificate_details[0]['heading'];?>
                            </h1>
                            <h4 class="title2">
                                <?php echo $certificate_details[0]['sub_heading'];?>
                            </h4>
                        </div>
                        <div class="certifiate_prodly_wrap">
                            <h2 class="certificate_Proudly">
                                <?php echo $certificate_details[0]['title'];?>
                            </h2>
                        </div>
    
                        <div class="certifiate_name_wrap">
                            <h2 class="name_title">
                                <?php echo html_escape($this->common->languageTranslator('ltr_student_name')); ?>
                            </h2>
                        </div>
                        <p class="certificate_discrip">
                            <?php echo $certificate_details[0]['description'];?>
                        </p>
    
                    </div>
                    <div class="certifiate_date">
                        <p>
                            <?php echo date('d-m-Y');?>
                        </p>
                    </div>
                    <div class="certifiate_sign">
                        <img src="<?php echo base_url();?>uploads/site_data/<?php echo $certificate_details[0]['signature_image'];?>"
                            alt="signature">
                    </div>
                    <div class="certifiate_main_logo">
                        <img src="<?php echo base_url();?>uploads/site_data/<?php echo $certificate_details[0]['certificate_logo'];?>"
                            class="certificate_logo" alt="Logo">
                    </div>
                </div>
            </div>
        </body>
    </html>
    <?php
}else if($certificate_details[0]['template_id']==2){?>
    <html lang="en">
        <head>
          <title>E-Academy Email Template 02</title>
          <meta charset="utf-8">
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
          <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
          <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@100;200;300;400;500;600;700;800;900&family=Great+Vibes&family=K2D:wght@100;200;300;400;500;600;700;800&display=swap" rel="stylesheet">
        
          <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@100;200;300;400;500;600;700;800;900&family=K2D:wght@100;200;300;400;500;600;700;800&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
        
          <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@100;200;300;400;500;600;700;800;900&family=Great+Vibes&family=K2D:wght@100;200;300;400;500;600;700;800&display=swap" rel="stylesheet">
          <style>
            h1,h1,h3,h4,h5,h6{
                padding: 0;
                margin: 0;
            }
            .ea_email_wrapper {
                background-image: url(<?=base_url();?>assets/images/certificate/temp1.jpg);
                background-repeat: no-repeat;
                width: 100%;
                height: 870px;
                background-position: center;
                max-width: 1205px;
                margin: 0 auto;
                position: relative;
                overflow: hidden;
                top: 30px;
            }
            .ea_email_logo {
                text-align: center;
                padding: 98px 0px 0;
                width: 100px;
                max-width: 100px;
                margin: 0 auto;
                height: 130px;
                position:relative;
                bottom:118px;
            }
            .ea_email_logo img {
                width: 165px;
                height: 180px;
                overflow: hidden;
                position: relative;
                top: 10px;
                padding: 25px 0px;
            }
            .ea_email_wrapper h5 {
                font-size: 90px;
                color: #fcb945;
                font-weight: 300;
                font-family: "Cinzel";
                text-align: center;
                padding: 10px 0 0px 0;
            }
            .ea_email_wrapper h4 {
                font-size: 45px;
                color: #929497;
                font-weight: 400;
                font-family: "Cinzel";
                text-align: center;
                padding: 0 0 10px 0;
            }
            .ea_email_wrapper h3 {
                font-size: 17px;
                color: #929497;
                font-weight: 400;
                font-family: "Cinzel";
                text-align: center;
                padding: 10px 0 10px 0;
            }
            .ea_email_wrapper h2 {
                font-size: 67px;
                color: #fcb945;
                font-weight: 400;
                font-family: "Great Vibes";
                text-align: center;
                padding: 15px 0 0 0;
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
            }
            .ea_email_year > h2 {
                font-size: 40px;
                color: #fcb945;
                font-weight: 900;
                font-family: "Great Vibes";
                text-align: center;
                padding: 50px 0 0 0;
            }
            .ea_email_date > h2 {
                font-size: 30px;
                color: #fcb945;
                font-weight: 400;
                font-family: "Great Vibes";
                text-align: left;
                padding: 0px 0px 0px 175px;
                position: relative;
                top: 155px;
            }
            .certifiate_sign img {
                position: absolute;
                right: 175px;
                bottom: 70px;
                width: 200px;
                height: 115px;
                background: transparent;
            }
            span.YearOnly {
                position: absolute;
                left: 563px;
                color: #fcb945;
                font-size: 40px;
                overflow: hidden;
                font-family: "Great Vibes";
                font-weight: bold;
                bottom: 184px;
            }
          </style>
        </head>
        <body>
        <div class="ea_email_wrapper">
           <div class="ea_email_logo"><img src="<?php echo base_url();?>uploads/site_data/<?php echo $certificate_details[0]['certificate_logo'];?>"></div>
           <h5> <?php echo $certificate_details[0]['heading'];?></h5>
           <h4><?php echo $certificate_details[0]['sub_heading'];?></h4>
           <h3> <?php echo $certificate_details[0]['title'];?></h3>
           <h2><?php echo html_escape($this->common->languageTranslator('ltr_student_name')); ?></h2>
           <div class="as_email_border"></div>
           
           <p><?php echo $certificate_details[0]['description'];?></p>
            <div class="ea_email_date">
                <h2><?php echo date('d-m-Y');?></h2>
            </div>
            <span class="YearOnly"><?php echo date('Y');?></span>
            <div class="certifiate_sign">
                <img src="<?php echo base_url();?>uploads/site_data/<?php echo $certificate_details[0]['signature_image'];?>"
                    alt="signature">
            </div>
        </div>
        </body>
        </html>
    <?php 
}else if($certificate_details[0]['template_id']==3){?>
    <html lang="en">
        <head> 
          <title>E-Academy Email Template 02</title>
          <meta charset="utf-8">
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
          <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
          <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@100;200;300;400;500;600;700;800;900&family=Great+Vibes&family=K2D:wght@100;200;300;400;500;600;700;800&display=swap" rel="stylesheet">
        
          <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@100;200;300;400;500;600;700;800;900&family=K2D:wght@100;200;300;400;500;600;700;800&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
        
          <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@100;200;300;400;500;600;700;800;900&family=Great+Vibes&family=K2D:wght@100;200;300;400;500;600;700;800&display=swap" rel="stylesheet">
          <style>
            h1,h1,h3,h4,h5,h6{
                padding: 0;
                margin: 0;
            }
            .ea_email_wrapper {
                background-image: url(<?=base_url();?>assets/images/certificate/template_two.png);
                background-repeat: no-repeat;
                width: 100%;
                height: 870px;
                background-position: center;
                max-width: 1205px;
                margin: 0 auto;
                position: relative;
                overflow: hidden;
                top: 30px;
            }
            .ea_email_logo {
                text-align: center;
                padding: 98px 0px 0;
                width: 150px;
                max-width: 150px;
                margin: 0 auto;
                height: 115px;
                position:relative;
                bottom:118px;
            }
            .ea_email_logo img {
                width: 165px;
                height: 180px;
                overflow: hidden;
                position: relative;
                top: 10px;
                padding: 25px 0px;
            }
            .ea_email_wrapper h5 {
              font-size: 125px;
                color: #fcb945;
                font-weight: 300;
                font-family: "Cinzel";
                text-align: center;
                padding: 0px 0 0px 0;
                position: relative;
                top: 10px;
            }
            .ea_email_wrapper h4 {
                font-size: 30px;
                color: #ffffff;
                font-weight: 400;
                font-family: "Cinzel";
                text-align: center;
                padding: 0;
                position: relative;
                top: 10px;
            }
            .ea_email_wrapper h3 {
                font-size: 35px;
                color: #929497;
                font-weight: 400;
                font-family: "Cinzel";
                text-align: center;
                padding: 0px 0 0px 0;
                position: relative;
                top: 35px;
            }
            .ea_email_wrapper h2 {
                font-size: 100px;
                color: #fcb945;
                font-weight: 400;
                font-family: "Great Vibes";
                text-align: center;
                /* line-height: 1.4; */
                margin: 0px 0px;
                position: relative;
                top: 75px;
                left: 0;
                right: 0;
                bottom: 0;
            }
            .as_email_border {
                width: 61%;
                height: 2px;
                background-color: #fcb945;
                margin: 0px auto 0;
                position: relative;
                top: 65px;
            }
            .ea_email_wrapper p {
                font-size: 18px;
                color: #fff;
                font-weight: 400;
                font-style: italic;
                font-family: "Raleway";
                text-align: center;
                width: 100%;
                max-width: 700px;
                margin: 0 auto;
                padding: 30px 0px 30px 0px;
                line-height: 2;
                position: relative;
                top: 60px;
                left: auto;
                right: auto;
            }
            .ea_email_year > h2 {
                font-size: 40px;
                color: #fcb945;
                font-weight: 900;
                font-family: "Great Vibes";
                text-align: center;
                padding: 50px 0 0 0;
            }
            .ea_email_date > h2 {
                font-size: 30px;
                color: #fcb945;
                font-weight: 400;
                font-family: "Great Vibes";
                text-align: left;
                padding: 0px 0 0px 175px;
                position: relative;
                /* bottom: 133px !important; */
                left: 140px;
            }
            .f_20px{
                font-size: 20px !important;
            }
            .certifiate_sign img {
                position: absolute;
                right: 300px;
                bottom: 104px;
                width: 190px;
                height: 100px;
                background: transparent;
            }
          </style>
        </head>
        <body>
        <div class="ea_email_wrapper">
           <div class="ea_email_logo"><img src="<?php echo base_url();?>uploads/site_data/<?php echo $certificate_details[0]['certificate_logo'];?>"></div>
           <h5> <?php echo $certificate_details[0]['heading'];?></h5>
           <h4><?php echo $certificate_details[0]['sub_heading'];?></h4>
           <h3> <?php echo $certificate_details[0]['title'];?></h3>
           <h2><?php echo html_escape($this->common->languageTranslator('ltr_student_name')); ?></h2>
           <div class="as_email_border"></div>
           
           <p><?php echo $certificate_details[0]['description'];?></p>
            <div class="ea_email_date">
                <h2><?php echo date('d-m-Y');?></h2>
            </div>
             <div class="certifiate_sign">
                <img src="<?php echo base_url();?>uploads/site_data/<?php echo $certificate_details[0]['signature_image'];?>"
                    alt="signature">
            </div>
        </div>
        </body>
    </html>
        <?php
}
?> 



