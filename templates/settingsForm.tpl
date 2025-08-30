{**
 * templates/settingsForm.tpl
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Plugin settings
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#indexNowSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="indexNowSettingsForm" method="post" action="{url router=PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	{csrf}
	<p id="description">{translate key="plugins.generic.indexNow.settings.description"}</p>
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="indexNowFormNotification"}

	{fbvFormArea id="indexNowSettingsFormArea"}
		{fbvFormSection for="key" title="plugins.generic.indexNow.settings.key"}
			{fbvElement type="text" name="key" id="indexNowKey" value=$key}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}
</form>

