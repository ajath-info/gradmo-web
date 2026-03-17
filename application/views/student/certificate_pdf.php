<!DOCTYPE html>
<?php 
   if($certificate_details[0]['template_id']==1){?>
    <html lang="en">
       <head>
          <meta charset="utf-8">
          <link rel="preconnect" href="https://fonts.googleapis.com">
          <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
          <link
             href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
             rel="stylesheet">
          <style type="text/css">
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
             padding: 140px 0 0 188px;
             }
             .title1 {
             font-size: 80px;
             text-transform: uppercase;
             color: #4d4a81;
             margin-bottom: 37px;
             
             }
             .title2 {
             font-size: 28px;
             font-weight: 400;
             text-transform: uppercase;
             color: #f0c586;
             position: relative;
             top: 20px;
             display: inline-block;
             padding: 0;
             margin: 0;
             padding-left: 255px;
             z-index: 1111;
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
             margin-top: 60px;
             }
             .certificate_discrip {
             font-size: 18px;
             text-align: center;
             width: 100%;
             max-width: 830px;
             margin: 0 auto;
             line-height: 20px;
             position: relative;
             }
             .edu_certifiate_wrapper {
             background: url("<?php echo base_url('assets/images/certificate_bg.jpg'); ?>");
             width: 1203px;
             height: 871px;
             margin: auto;
             position: relative;
             }
             .certifiate_main_logo {
             position: absolute;
             right: 163px;
             top: 183px;
             width: 100%;
             height: 100px;
             max-width: 120px;
             overflow: hidden;
             }
             .certifiate_main_logo img {
             width: 100%;
             height: 100%;
             }
             .certifiate_date {
             position: absolute;
             top: 630px;
             left: 233px;
             color: #4d4784;
             font-weight: 500;
             font-size: 20px;
             }
             .certifiate_sign {
             position: absolute;
             right: 171px;
             top: 640px;
             width: 100px;
             height: 50px;
             }
             .certifiate_sign>img {
             width: 100px;
             height: 50px;
             object-fit: contain;
             }
             .title2:after {
             /*content: "";*/
             /*position: absolute;*/
             /*left: -175px;*/
             /*width: 420px;*/
             /*top: 10px;*/
             /*height: 23px;*/
             /*background: #fcd195;*/
             /*bottom: 0;*/
             /*margin: auto;*/
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
             .certificate_img_css {
             width: 100%;
             max-width: 1203px;
             height: 871px;
             margin: auto;
             position: absolute;
             top: 0;
             bottom: 0;
             left: 0;
             right: 0;
             z-index: -1;
             background-position: center;
             overflow: hidden;
             }
             .rtl .edu_certifiate_wrapper {
             background: url("<?php echo base_url('assets/images/rtl/certificate_bg.jpg'); ?>");
             }
          </style>
       </head>
       <body class="form">
          <?php if(!empty($student_certificate)){?>
          <div class="conatiner_certificate">
          <div class="edu_certifiate_wrapper">
             <?php echo '<img  class="edu_certifiate_Bgwrapper certificate_logo certificate_img_css" src="data:image/jpg;base64,'.base64_encode(file_get_contents(base_url().'assets/images/certificate_bg.jpg')).'"  alt="Logo">';?>
             <div class="edu_certifiate_inner">
                <div class="edu_certifiate_heading">
                   <h1 class="title1">
                      <?php echo $certificate_details[0]['heading']?>
                   </h1>
                   <h4 class="title2">
                      <?php echo $certificate_details[0]['sub_heading']?>
                   </h4>
                </div>
                <div class="certifiate_prodly_wrap">
                   <h2 class="certificate_Proudly">
                      <?php echo $certificate_details[0]['title']?>
                   </h2>
                </div>
                <div class="certifiate_name_wrap">
                   <h2 class="name_title">
                      <?php echo $student_details[0]['name'];?>
                   </h2>
                </div>
                <p class="certificate_discrip">
                   <?php echo str_replace('{batch}','<b>'.$batchdata[0]['batch_name'].'</b>', $certificate_details[0]['description']);?>
                </p>
             </div>
             <div class="certifiate_date">
                <p>
                   <?php echo date('d-m-Y',strtotime($student_certificate[0]['date']));?>
                </p>
             </div>
             <div class="certifiate_sign">
                <?php echo ' <img src="data:image/jpg;base64,'.base64_encode(file_get_contents(base_url().'uploads/site_data/'.$certificate_details[0]['signature_image'])).'" alt="signature">';?>
             </div>
             <div class="certifiate_main_logo">
                <?php echo '<img  class="edu_certifiate_Bgwrapper" src="data:image/jpg;base64,'.base64_encode(file_get_contents(base_url().'uploads/site_data/'.$certificate_details[0]['certificate_logo'])).'" class="certificate_logo" alt="Logo">';?>
             </div>
          </div>
          <?php  }else{
             echo "no data available";
             
             } ?>
       </body>
    </html><?php
    }else if($certificate_details[0]['template_id']==2){?>
   <html lang="en">
       <head>
          <meta charset="utf-8">
          <link rel="preconnect" href="https://fonts.googleapis.com">
          <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
          <link
             href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
             rel="stylesheet">
          <style type="text/css">
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
             padding: 140px 0 0 188px;
             }
             .title1 {
              text-transform: uppercase;
             font-size: 90px;
             color: #fcb945;
             font-weight: 300;
             font-family: "Cinzel";
             text-align: center;
             position:relative;
             left:-100px;
             }
             .title2 {
             font-size: 28px;
             font-weight: 400;
             text-transform: uppercase;
             color: #f0c586;
             position: relative;
             top: 20px;
             display: inline-block;
             padding: 0;
             margin: 0;
             z-index: 1111;
             padding-left:200px;
             }
             .certifiate_prodly_wrap {
             text-align: center;
             }
             .certificate_Proudly {
             font-size: 26px;
             color: #4d4784;
             font-weight: 500;
             margin-top: 40px;
             padding-left:10px;
             }
             .name_title {
                font-size: 67px;
                color: #fcb945; 
                font-weight: 400;
                font-family: "Great Vibes";
                text-align: center;
             }
             .certificate_discrip {
             font-size: 18px;
             text-align: center;
             width: 100%;
             max-width: 720px;
             margin: 0 auto;
             line-height: 20px;
             position: relative;
             padding: 28px 0px 30px 0px;
             }
             .edu_certifiate_wrapper {
             background: url("<?php echo base_url('assets/images/certificate_bg.jpg'); ?>");
             width: 1203px;
             height: 871px;
             margin: auto;
             position: relative;
             }
             .certifiate_main_logo {
             position: absolute;
             right: 47%;
             top: 20px;
             width: 100%;
             height: 100px;
             max-width: 120px;
             overflow: hidden;
             }
             .certifiate_main_logo img {
             width: 100%;
             height: 100%;
             }
             .certifiate_date {
             position: absolute;
             top: 715px;
             left: 200px;
             color: #4d4784;
             font-weight: 500;
             font-size: 20px;
             }
             .certifiate_sign {
             position: absolute;
             right: 235px;
             top: 660px;
             width: 100px;
             height: 50px;
             } 
             .certifiate_sign>img {
             width: 180px;
             height: 150px;
             object-fit: contain;
             }
             /*.title2:after {*/
             /*content: "";*/
             /*position: absolute;*/
             /*left:50px;*/
             /*width: 720px;*/
             /*margin:0 auto;*/
             /*top: 200px;*/
             /*height: 2px;*/
             /*background: #465f91;*/
             /*bottom: 0;*/
             /*margin: auto;*/
             /*}*/
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
             .certificate_img_css {
             width: 100%;
             max-width: 1203px;
             height: 871px;
             margin: auto;
             position: absolute;
             top: 0;
             bottom: 0;
             left: 0;
             right: 0;
             z-index: -1;
             background-position: center;
             overflow: hidden;
             }
             .rtl .edu_certifiate_wrapper {
             background: url("<?php echo base_url('assets/images/rtl/certificate_bg.jpg'); ?>");
             }
             .certifiate_name_wrap h2{
                padding: 17px 0 0 0;
             }
             h4.YearOnly {
                position: absolute;
                left: 552px;
                color: #fcb945;
                font-size: 40px;
                overflow: hidden;
                font-family: "Cinzel";
                font-weight: bold;
                top: 620px;
            }
          </style>
       </head>
       <body class="form">
          <?php if(!empty($student_certificate)){?>
          <div class="conatiner_certificate">
          <div class="edu_certifiate_wrapper">
             <?php echo '<img  class="edu_certifiate_Bgwrapper certificate_logo certificate_img_css" src="data:image/jpg;base64,'.base64_encode(file_get_contents(base_url().'assets/images/certificate/temp1.jpg')).'"  alt="Logo">';?>
             <div class="edu_certifiate_inner">
                <div class="edu_certifiate_heading">
                   <h1 class="title1">
                      <?php echo $certificate_details[0]['heading']?>
                   </h1>
                   <h4 class="title2">
                      <?php echo $certificate_details[0]['sub_heading']?>
                   </h4>
                </div>
                <div class="certifiate_prodly_wrap">
                   <h2 class="certificate_Proudly">
                      <?php echo $certificate_details[0]['title']?>
                   </h2>
                </div>
                <div class="certifiate_name_wrap">
                   <h2 class="name_title">
                      <?php echo $student_details[0]['name'];?>
                   </h2>
                </div>
                <p class="certificate_discrip">
                   <?php echo str_replace('{batch}','<b>'.$batchdata[0]['batch_name'].'</b>', $certificate_details[0]['description']);?>
                </p>
             </div>
             <div class="certifiate_date">
                <p>
                   <?php echo date('d-m-Y',strtotime($student_certificate[0]['date']));?>
                </p>
             </div>
             <div class="certifiate_sign">
                <?php echo ' <img src="data:image/jpg;base64,'.base64_encode(file_get_contents(base_url().'uploads/site_data/'.$certificate_details[0]['signature_image'])).'" alt="signature">';?>
             </div>
             <div class="certifiate_main_logo">
                <?php echo '<img  class="edu_certifiate_Bgwrapper" src="data:image/jpg;base64,'.base64_encode(file_get_contents(base_url().'uploads/site_data/'.$certificate_details[0]['certificate_logo'])).'" class="certificate_logo" alt="Logo">';?>
             </div>
             <h4 class="YearOnly">  <?php echo date('Y',strtotime($student_certificate[0]['date']));?> </h4>
          </div>
          <?php  }else{
             echo "no data available";
             
             } ?>
       </body>
    </html>
    <?php 
    }else if($certificate_details[0]['template_id']==3){?>
     <html lang="en">
       <head>
          <meta charset="utf-8">
          <link rel="preconnect" href="https://fonts.googleapis.com">
          <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
          <link rel="preconnect" href="https://fonts.googleapis.com">
          <link rel="preconnect" href="https://fonts.googleapis.com">
          <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
          <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
                      <link
             href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
             rel="stylesheet">
          <style type="text/css">
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
             padding: 140px 0 0 188px;
             }
             .title1 {
              text-transform: uppercase;
             font-size: 90px;
             color: #fcb945;
             font-weight: 300;
             font-family: "Cinzel";
             text-align: center;
             position:relative;
             left:-100px;
             }
             .title2 {
             font-size: 28px;
             font-weight: 400;
             text-transform: uppercase;
             color: #f0c586;
             position: relative;
             top: 20px;
             display: inline-block;
             padding: 0;
             margin: 0;
             z-index: 1111;
             padding-left:200px;
             }
             .certifiate_prodly_wrap {
             text-align: center;
             }
             .certificate_Proudly {
             font-size: 35px;
             color: #ffffff;
            font-family: "Cinzel";
             font-weight: 500;
             margin-top: 30px;
             padding-left:10px;
             }
             .name_title {
                font-size: 110px;
                color: #fcb945; 
                font-weight: 400;
                font-family: "Great Vibes";
                text-align: center;
                margin-top:60px;
             }
             
             .certificate_discrip {
             font-size: 18px;
             text-align: center;
             width: 100%;
             max-width: 720px;
             margin: 0 auto;
             line-height: 20px;
             position: relative;
             top:-20px;
             padding: 30px 0px 30px 0px;
             }
             .edu_certifiate_wrapper {
             background: url("<?php echo base_url('assets/images/certificate_bg.jpg'); ?>");
             width: 1203px;
             height: 871px;
             margin: auto;
             position: relative;
             }
             .certifiate_main_logo {
             position: absolute;
             right: 47%;
             top: 20px;
             width: 100%;
             height: 100px;
             max-width: 120px;
             overflow: hidden;
             }
             .certifiate_main_logo img { 
             width: 100%;
             height: 100%;
             }  
              .certifiate_date {
             position: absolute;
             top: 670px;
             left: 280px;
             color: #4d4784;
             font-weight: 500;
             font-size: 20px;
             }  
             .certifiate_sign {
             position: absolute;
             right: 370px;
             top: 610px;
             width: 100px;
             height: 50px;
             } 
             .certifiate_sign>img {
             width: 180px;
             height: 150px;
             object-fit: contain;
             }
             /*.title2:after {*/
             /*content: "";*/
             /*position: absolute;*/
             /*left:50px;*/
             /*width: 720px;*/
             /*margin:0 auto;*/
             /*top: 200px;*/
             /*height: 2px;*/
             /*background: #465f91;*/
             /*bottom: 0;*/
             /*margin: auto;*/
             /*}*/
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
             .certificate_img_css {
             width: 100%;
             max-width: 1203px;
             height: 871px;
             margin: auto;
             position: absolute;
             top: 0;
             bottom: 0;
             left: 0;
             right: 0;
             z-index: -1;
             background-position: center;
             overflow: hidden;
             }
             .rtl .edu_certifiate_wrapper {
             background: url("<?php echo base_url('assets/images/rtl/certificate_bg.jpg'); ?>");
             }
             .certifiate_name_wrap h2{
                padding: 17px 0 0 0;
             }
             h4.YearOnly {
                position: absolute;
                left: 552px;
                color: #fcb945;
                font-size: 40px;
                overflow: hidden;
                font-family: "Cinzel";
                font-weight: bold;
                top: 620px;
            }
          </style>
       </head>
       <body class="form">
          <?php if(!empty($student_certificate)){?>
          <div class="conatiner_certificate">
          <div class="edu_certifiate_wrapper">
             <?php echo '<img  class="edu_certifiate_Bgwrapper certificate_logo certificate_img_css" src="data:image/jpg;base64,'.base64_encode(file_get_contents(base_url().'assets/images/certificate/temp2.png')).'"  alt="Logo">';?>
             <div class="edu_certifiate_inner">
                <div class="edu_certifiate_heading">
                   <h1 class="title1">
                      <?php echo $certificate_details[0]['heading']?>
                   </h1>
                   <h4 class="title2">
                      <?php echo $certificate_details[0]['sub_heading']?>
                   </h4>
                </div>
                <div class="certifiate_prodly_wrap">
                   <h2 class="certificate_Proudly">
                      <?php echo $certificate_details[0]['title']?>
                   </h2>
                </div>
                <div class="certifiate_name_wrap">
                   <h2 class="name_title">
                      <?php echo $student_details[0]['name'];?>
                   </h2>
                </div>
                <p class="certificate_discrip">
                   <?php echo str_replace('{batch}','<b>'.$batchdata[0]['batch_name'].'</b>', $certificate_details[0]['description']);?>
                </p>
             </div>
             <div class="certifiate_date">
                <p>
                   <?php echo date('d-m-Y',strtotime($student_certificate[0]['date']));?>
                </p>
             </div>
             <div class="certifiate_sign">
                <?php echo ' <img src="data:image/jpg;base64,'.base64_encode(file_get_contents(base_url().'uploads/site_data/'.$certificate_details[0]['signature_image'])).'" alt="signature">';?>
             </div>
             <div class="certifiate_main_logo">
                <?php echo '<img  class="edu_certifiate_Bgwrapper" src="data:image/jpg;base64,'.base64_encode(file_get_contents(base_url().'uploads/site_data/'.$certificate_details[0]['certificate_logo'])).'" class="certificate_logo" alt="Logo">';?>
             </div>
             <h4 class="YearOnly">  <?php echo date('Y',strtotime($student_certificate[0]['date']));?> </h4>
          </div>
          <?php  }else{
             echo "no data available";
             
             } ?>
       </body>
    </html>
        <?php
}
?> 
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.5/jspdf.min.js"></script>
<script>
    (function () {
        var
            form = $('.form'),
            cache_width = form.width(),
            a4 = [595.28, 841.89]; // for a4 size paper width and height  

        $('#create_pdf').on('click', function () {
            $('body').scrollTop(0);
            $(this).hide();
            createPDF();
        });
        //create pdf  
        function createPDF() {
            getCanvas().then(function (canvas) {
                var
                    img = canvas.toDataURL("image/png"),
                    doc = new jsPDF({
                        unit: 'px',
                        format: [545.28, 725],
                        orientation: 'landscape'
                    });

                doc.addImage(img, 'JPEG', 20, 20);
                doc.save('<?php echo $this->session->userdata('name');?>' + '.pdf');
                form.width(cache_width);
            });
        }

        // create canvas object  
        function getCanvas() {
            form.width((a4[0] * 1)).css('max-width', 'none');
            return html2canvas(form, {
                imageTimeout: 2000,
                removeContainer: true
            });
        }

    }());  
</script>
<script>
    $(document).on('click', '#dwl_create_pdf', function () {

        var pdfu = $(this).attr('data-pdfu');
        var pdfb = $(this).attr('data-pdfb');
        var base_url = $(this).attr('data-pdf_url');
        if (pdfu) {
            $.ajax({
                method: "POST",
                url: base_url + 'ajaxcall/certificate_pdf_view',
                data: { 'pdfb': pdfb, 'pdfu': pdfu },
                success: function (resp) {
                    var resp = $.parseJSON(resp);
                    if (resp['status'] == '1') {
                        var file_path = resp['filesUrl'] + resp['fileName'];
                        var a = document.createElement('A');
                        a.href = file_path;
                        a.download = file_path.substr(file_path.lastIndexOf('/') + 1);
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    } else if (resp['status'] == '2') {
                        console.log(resp['msg']);
                    } else {
                        console.log('Something went wrong, Please try again.');
                    }
                    $('.edu_preloader').fadeOut();
                },
                error: function (resp) {
                    console.log('Something went wrong, Please try again.');
                    $('.edu_preloader').fadeOut();
                }
            });
        }
    });
    (function ($) {
        $.fn.html2canvas = function (options) {
            var date = new Date(),
                $message = null,
                timeoutTimer = false,
                timer = date.getTime();
            html2canvas.logging = options && options.logging;
            html2canvas.Preload(this[0], $.extend({
                complete: function (images) {
                    var queue = html2canvas.Parse(this[0], images, options),
                        $canvas = $(html2canvas.Renderer(queue, options)),
                        finishTime = new Date();

                    $canvas.css({ position: 'absolute', left: 0, top: 0 }).appendTo(document.body);
                    $canvas.siblings().toggle();

                    $(window).click(function () {
                        if (!$canvas.is(':visible')) {
                            $canvas.toggle().siblings().toggle();
                            throwMessage("Canvas Render visible");
                        } else {
                            $canvas.siblings().toggle();
                            $canvas.toggle();
                            throwMessage("Canvas Render hidden");
                        }
                    });
                    throwMessage('Screenshot created in ' + ((finishTime.getTime() - timer) / 1000) + " seconds<br />", 4000);
                }
            }, options));

            function throwMessage(msg, duration) {
                window.clearTimeout(timeoutTimer);
                timeoutTimer = window.setTimeout(function () {
                    $message.fadeOut(function () {
                        $message.remove();
                    });
                }, duration || 2000);
                if ($message)
                    $message.remove();
                $message = $('<div ></div>').html(msg).css({
                    margin: 0,
                    padding: 10,
                    background: "#000",
                    opacity: 0.7,
                    position: "fixed",
                    top: 10,
                    right: 10,
                    fontFamily: 'Tahoma',
                    color: '#fff',
                    fontSize: 12,
                    borderRadius: 12,
                    width: 'auto',
                    height: 'auto',
                    textAlign: 'center',
                    textDecoration: 'none'
                }).hide().fadeIn().appendTo('body');
            }
        };
    })(jQuery);

</script>