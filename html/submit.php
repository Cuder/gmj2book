<form name="submitData" method="post" action="index.php?submit" onsubmit="return validate()">
	<fieldset class="fieldset">
		<legend class="legend"><?php echo $textSubmitForm[13];?></legend>
		<p>
			<?php echo $textSubmitForm[10];?>:<br>
			<select name="site" id="site" class="dropdown">
				<option value="0"><?php echo $textSubmitForm[11];?></option>
				<option value="1"><?php echo $textSubmitForm[12];?></option>
			</select>
		</p>
		<p>
			<?php echo $textSubmitForm[0];?>:<br>
			<input name="blogName" id="blogName" maxlength="20" placeholder="<?php echo $textSubmitForm[1];?>" type="text" class="textbox"><br>
		</p>
		<p>
			<?php echo $textSubmitForm[2].$textSubmitForm[23];?>:<br>
			<input name="coauthor" id="coauthor" maxlength="20" placeholder="<?php echo $textSubmitForm[3];?>" type="text" class="textbox"><br>
			<?php echo $textSubmitForm[4];?>
		</p>
	</fieldset>
	<fieldset class="fieldset">
		<legend class="legend"><?php echo $textSubmitForm[14];?></legend>
		<p>
			<?php echo $textSubmitForm[15].$textSubmitForm[23];?>:<br>
			<input name="realName" id="realName" maxlength="25" placeholder="<?php echo $textSubmitForm[20];?>" type="text" class="textbox"><br>
		</p>
		<p>
			<?php echo $textSubmitForm[16].$textSubmitForm[23];?>:<br>
			<input name="realSurname" id="realSurname" maxlength="25" placeholder="<?php echo $textSubmitForm[21];?>" type="text" class="textbox"><br>
		</p>
		<p>
			<input type="checkbox" name="images" id="images" value="include" checked="checked"><?php echo $textSubmitForm[22];?>
		</p>
	</fieldset>
	<fieldset class="fieldset">
		<legend class="legend"><?php echo $textSubmitForm[5];?></legend>
		<p>
			<input name="email" id="email" maxlength="100" placeholder="<?php echo $textSubmitForm[6];?>" type="text" class="textbox" value="me@nikitakovin.ru"><br>
			<?php echo $textSubmitForm[7];?>
		</p>
	</fieldset>
	<p>
		<input id="submitButton" type="submit" name="submitButton" value="<?php echo $textSubmitForm[8];?>">
		<input id="clearData" type="reset" value="<?php echo $textSubmitForm[9];?>">
	</p>
</form>
<script type="text/javascript">
	var error0 = "<?= $textErrors[0] ?>";
	var error1 = "<?= $textErrors[1] ?>";
	var error2 = "<?= $textErrors[2] ?>";
	var error3 = "<?= $textErrors[3] ?>";
	var error4 = "<?= $textErrors[4] ?>";
</script>
<script type="text/javascript" src="../js/checkInputForm.js"></script>
