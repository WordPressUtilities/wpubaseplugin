<?php
if (!isset($email_logo)) {
    $email_logo = get_header_image();
}
if (!isset($footer_link)) {
    $footer_link = get_site_url();
}
if (!isset($footer_text)) {
    $footer_text = get_bloginfo('name');
}
if (!isset($email_text)) {
    $email_text = 'Hello World';
}


$email_logo = apply_filters('wpubaseemail__template__email_logo', $email_logo);
$body_background_color = apply_filters('wpubaseemail__template__body_background_color', '#F0F0F0');
$main_background_color = apply_filters('wpubaseemail__template__main_background_color', '#FFFFFF');
$main_text_color = apply_filters('wpubaseemail__template__main_text_color', '#000000');
$main_font_size = apply_filters('wpubaseemail__template__main_font_size', 16);
$logo_width = apply_filters('wpubaseemail__template__logo_width', 100);
$divider_color = apply_filters('wpubaseemail__template__divider_color', '#F0F0F0');
$font_family = apply_filters('wpubaseemail__template__font_family', 'arial, helvetica, sans-serif');

?><!doctype html><html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"><head><title></title><!--[if !mso]><!--><meta http-equiv="X-UA-Compatible" content="IE=edge"><!--<![endif]--><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style type="text/css">#outlook a { padding:0; }
          body { margin:0;padding:0;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%; }
          table, td { border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt; }
          img { border:0;height:auto;line-height:100%; outline:none;text-decoration:none;-ms-interpolation-mode:bicubic; }
          p { display:block;margin:13px 0; }</style><!--[if mso]>
        <noscript>
        <xml>
        <o:OfficeDocumentSettings>
          <o:AllowPNG/>
          <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
        </xml>
        </noscript>
        <![endif]--><!--[if lte mso 11]>
        <style type="text/css">
          .mj-outlook-group-fix { width:100% !important; }
        </style>
        <![endif]--><style type="text/css">@media only screen and (min-width:480px) {
        .mj-column-per-100 { width:100% !important; max-width: 100%; }
      }</style><style media="screen and (min-width:480px)">.moz-text-html .mj-column-per-100 { width:100% !important; max-width: 100%; }</style><style type="text/css">@media only screen and (max-width:480px) {
      table.mj-full-width-mobile { width: 100% !important; }
      td.mj-full-width-mobile { width: auto !important; }
    }</style></head><body style="word-spacing:normal;background-color:<?php echo $body_background_color; ?>;"><div style="background-color:<?php echo $body_background_color; ?>;"><!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" bgcolor="<?php echo $main_background_color; ?>" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]--><div style="background:<?php echo $main_background_color; ?>;background-color:<?php echo $main_background_color; ?>;margin:0px auto;max-width:600px;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:<?php echo $main_background_color; ?>;background-color:<?php echo $main_background_color; ?>;width:100%;"><tbody><tr><td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;"><!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:600px;" ><![endif]--><div class="mj-column-per-100 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"><tbody><tr><td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;"><tbody><tr><td style="width:<?php echo $logo_width; ?>px;"><img height="auto" src="<?php echo $email_logo; ?>" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="<?php echo $logo_width; ?>"></td></tr></tbody></table></td></tr><tr><td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"><p style="border-top:solid 1px <?php echo $divider_color; ?>;font-size:1px;margin:0px auto;width:100%;"></p><!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" style="border-top:solid 1px <?php echo $divider_color; ?>;font-size:1px;margin:0px auto;width:550px;" role="presentation" width="550px" ><tr><td style="height:0;line-height:0;"> &nbsp;
</td></tr></table><![endif]--></td></tr><tr><td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;"><div style="font-family:<?php echo $font_family; ?>;font-size:<?php echo $main_font_size; ?>px;line-height:1.3;text-align:left;color:<?php echo $main_text_color; ?>;"><?php echo $email_text; ?></div></td></tr><tr><td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"><p style="border-top:solid 1px <?php echo $divider_color; ?>;font-size:1px;margin:0px auto;width:100%;"></p><!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" style="border-top:solid 1px <?php echo $divider_color; ?>;font-size:1px;margin:0px auto;width:550px;" role="presentation" width="550px" ><tr><td style="height:0;line-height:0;"> &nbsp;
</td></tr></table><![endif]--></td></tr><tr><td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"><div style="font-family:<?php echo $font_family; ?>;font-size:12px;line-height:1.3;text-align:center;color:<?php echo $main_text_color; ?>;"><a class="link-nostyle" href="<?php echo $footer_link; ?>" style="color: inherit;"><?php echo $footer_text; ?></a></div></td></tr></tbody></table></div><!--[if mso | IE]></td></tr></table><![endif]--></td></tr></tbody></table></div><!--[if mso | IE]></td></tr></table><![endif]--></div></body></html>
