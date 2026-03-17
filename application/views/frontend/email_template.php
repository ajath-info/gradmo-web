
         <table cellpadding="0" cellspacing="0" border="0" style="padding:0px 0 0;/* background-color:#ffffff; */padding-bottom:0;font-family:'Roboto',sans-serif;width:633px;margin:0 auto;margin-bottom:0px;border:none;background-image: url('<?=base_url();?>assets/images/cerulean-blue-curve-frame-template_53876-99029.avif');background-size: cover;background-repeat: no-repeat;object-fit: cover;background-position: center;">
            <tr style="-webkit-font-smoothing: antialiased;  height: 100%;  -webkit-text-size-adjust: none;  width: 100% !important;">
               <td align="center" style=" float: left; padding: 40px 0px 15px;text-align: center;width:100%;background-image: url(https://gptblaster.co.in/assets/images/et_backgroundshape.png); background-size: cover;background-position: center;">
                  <span style="padding-right: 10px;text-align: center;display:inline-block;width:100%;max-width: 220px;">
                    <img src="<?=$site_logo; ?>" style="width: 216px;height: 45px;">    
                  </span>
               </td>
            </tr>
            <tr>
               <td>
                  <p style="margin: 0px 0 0;font-size:18px; color:#58616b;font-weight: 600; text-align:left;padding: 0px 50px 0px;">
                     <?php
                        $username = !empty($name) ? $name : 'There';
                        echo 'Hi '.$username.',';
                        ?>
                  </p>
                  <!-- <div style="height:20px;"></div> -->
               </td>
            </tr>
            <tr>
               <td>
                <?php if($status == 'update'){ ?>
                     <tr>
                        <td>
                           <p style="margin: 0;font-size:16px; color:#58616b;font-weight: 400; text-align:left;padding: 10px 50px 10px;">
                              We have updated your <?=$site_Name;?> account.
                           </p>
                        </td>
                     </tr>
                     <tr>
                        <td>
                          
                           <p style="margin: 0;font-size:16px; color:#58616b;font-weight: 400; text-align:left;padding: 0px 50px 10px;">
                              We are glad to have you as a part of <?=$site_Name;?>.
                           </p>
                           <div style="height:10px;"></div>
                        </td>
                     </tr>
                     <?php }elseif($status == 'new' || $status=='Reset' || $status=='Update'){ ?> 
                     <tr>
                        <td>
                            <p style="margin: 0;font-size:16px; color:#58616b;font-weight: 400; text-align:left;padding: 10px 50px 10px;">
                                <?php 
                                    if(!empty($productname)){
                                ?>
                                Thank you for Purchase Course :-
                                 <div class="row product_main" style="margin: 0;font-size:16px;color:#58616b;font-weight: 400;text-align:left; padding:0px 50px">
                                 <?php 
                                     if(isset($productname)){
                                         
                                        foreach($productname as $key=>$value){
                                            $bch = $value['batch_name'];
                                            echo "<p><span style='width: 8px;height: 8px;background: #ff9548; display: inline-block;border-radius: 10px;    margin: 0px 10px 0 10px;    position: relative;    left: -10px;    top: -3px;'></span>$bch</p>";                                       
                                          
                                        }
                                     }else{
                                         echo "<p><span style='width: 8px;height: 8px;background: #ff9548; display: inline-block;border-radius: 10px;    margin: 0px 10px 0 10px;    position: relative;    left: -10px;    top: -3px;'></span>$proName</p>";                                       
                                     }
                                    }
                                 ?>
                            </div>
                            </p>
                           <p style="margin: 0;font-size:16px; color:#58616b;font-weight: 400; text-align:left;padding: 10px 50px 10px;">
                             <?php if($status=='Reset'){echo "Congratulations! We have  Reset Password your account on '.$site_Name.' Area . Please login using below link.";}elseif($status=='Update'){echo "Congratulations! We have Update You Course";}else{echo "Congratulations! We have  created your account on '.$site_Name.' Area . Please login using below link.";}?>
                           </p>
                           <p style="margin: 15px 0 0;font-size:16px; color:#58616b;font-weight: 400; text-align:left;padding:0px 50px 10px;">
                              <b>Your <?php if($status!='Update'){?>Login<?php }?> Details</b>
                              <?php 
                                if($status!='Update'){
                                    ?>
                                    <span style="display:block; height:10px;"></span>
                                    <b>Login URL: </b><?= $link; ?>
                                    <?php 
                                }
                              ?>
                              <span style="display:block; height:10px;"></span>
                              <b>Username: </b><?= $email; ?>
                              <span style="display:block; height:10px;"></span>
                               <?php if($status!='Update'){?>
                                 <b><?php if($status=='Reset'){echo "New Password";}else{echo "Password";}?>: </b><?= $password; ?>
                               <?php 
                                }
                              ?>
                           </p>
                           <p style="margin: 15px 0 0;font-size:16px; color:#58616b;font-weight: 400; text-align:left;padding: 0px 50px 10px;">
                              We are glad to have you as a part of <?=$site_Name;?> .
                           </p>
                          
                           <p style="margin: 10px 0 25px; font-size:16px; color:#58616b;font-weight: 400; text-align:left;padding: 0px 50px 10px;">
                              If you have any questions or doubts please contact us by sending email at: <a style="font-size:15px;     color: #ff9548;font-weight: 600;"href="mailto:<?=$supportURL?>"><?=$supportURL?></a> 
                           </p>
                        </td>
                     </tr>
                     <?php }?>
                 </td>
                 <tr>
                     <td>
                         <p style="margin: 10px 0 25px; font-size:16px; color:#58616b;font-weight: 400; text-align:left;padding: 0px 50px 10px;">
                            <b>To Your Success,</b>
                            <span style="display:block; height:10px;"></span>
                            <b><?=$site_Name;?> Team</b>
                         </p>
                     </td>
                 </tr>
             </tr>	
             <tr style=" border: none; width: 100%; padding:11px 20px 12px; background-image: -webkit-linear-gradient( 0deg, rgb(255,154,68) 0%, rgb(252,96,118) 100%);">
                <td >
                    <p style="font-size:14px; padding:8px 0; color:#ffffff; font-size:16px;text-align:center;">
                        © <?= date('Y'); ?> Copyright <?=$site_Name;?> . All Rights reserved.
                    </p>
                </td>
             </tr>
         </table>


