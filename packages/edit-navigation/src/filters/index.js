/**
 * Internal dependencies
 */
import addNavigationEditorCustomAppender from './add-navigation-editor-custom-appender';
import addMenuNameEditor from './add-menu-name-editor';
import removeSettingsUnsupportedFeatures from './remove-settings-unsupported-features';

export const addFilters = () => {
	addNavigationEditorCustomAppender();
	addMenuNameEditor();
	removeSettingsUnsupportedFeatures();
};
