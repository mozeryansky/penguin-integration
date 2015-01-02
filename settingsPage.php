<?php
/*  Copyright 2014  Michael Ozeryansky  (email : mozer624@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<div class="wrap">
    <h2>Penguin Integration</h2>
    
    <?php if(isset($error)){ ?>
    <div class="error"><?php echo $error; ?><br />Please try again.</div>
  	<?php } ?>
  	
  	<h3>
        <a href="http://getpenguin.com/products" target="_blank">Manage Products</a>
        <a style="margin-left: 20px" href="http://getpenguin.com/transactions" target="_blank">View Transactions</a>
    </h3>
  	
    <h3>Business Login</h3>
    
    <div>Login to download all of your current products.</div>
    <div>Don't have an account? Go to <a href="http://getpenguin.com/signup?wp" target="_blank">getpenguin.com</a> to begin.</div>
    
    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
        <table cellpadding="2">
            <tr>
                <td align="right"><label for="email">Email</label></td>
                <td><input type="email" placeholder="Email" name="penguin_email" value="<?php echo isset($email)?$email:''; ?>"  required="required"></td>
            </tr>
            <tr>
                <td align="right"><label for="password">Password</label></td>
                <td><input type="password" placeholder="Password" name="penguin_password" required="required"></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td><a href="http://getpenguin.com/forgot?wp" target="_blank">Forgot your password?</a></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td><?php submit_button('Download Products', 'primary', 'penguin_download', false); ?></td>
            </tr>
        </table>
    </form>
    
    <br>
    
    <?php if(isset($lastDownload)){ ?>
    <div>Last download: <?php echo $lastDownload; ?></div>
    <?php } ?>
    
    <h3>Products</h3>
    
    <table cellpadding="5">
        <tr>
            <th align="left">Name</th>
            <th align="left">Shortcode (copy and paste into a post or page)</th>
        </tr>
    <?php if(!empty($products)){ ?>
        <?php foreach($products as $product){ ?>
        <tr>
            <td>
                <a href="http://getpenguin.com/products/edit?productId=<?php echo $product['id']; ?>" target="_blank"><?php echo $product['name']; ?></a>
            </td>
            <td>
                <pre>[penguin productid='<?php echo $product['id']; ?>']</pre>
            </td>
        </tr>
        <?php } ?>
    <?php } else { ?>
        <tr>
            <td colspan="3">Login to download your products</td>
        </tr>
    <?php } ?>
    </table>
    
</div>
