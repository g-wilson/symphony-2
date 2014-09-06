<?php

    /**
     * @package boot
     */
    /**
     * Handles autoloading of Symphony's objects. For now, this is not
     * really 'autoloading', it's just collecting all the require statements
     * scattered through the application and puts them in one spot
     */
    require_once DOCROOT . '/symphony/lib/boot/func.utilities.php';
    require_once DOCROOT . '/symphony/lib/boot/defines.php';
    require_once TOOLKIT . '/util.validators.php';

    function symphony_loader($class) {
        $class = strtolower($class);
        $classes = array(
            // Installer
            'installer' => INSTALL . '/lib/class.installer.php',
            'installerpage' => INSTALL . '/lib/class.installerpage.php',
            'updater' => INSTALL . '/lib/class.updaterpage.php',
            'updaterpage' => INSTALL . '/lib/class.updaterpage.php',
            'migration' => INSTALL . '/lib/class.migration.php',

            // Interfaces
            'cache' => FACE . '/interface.cache.php',
            'exportablefield' => FACE . '/interface.exportablefield.php',
            'fileresource' => FACE . '/interface.fileresource.php',
            'importablefield' => FACE . '/interface.importablefield.php',
            'provider' => FACE . '/interface.provider.php',
            'singleton' => FACE . '/interface.singleton.php',

            // Core
            'cacheable' => CORE . '/class.cacheable.php',
            'configuration' => CORE . '/class.configuration.php',
            'cookie' => CORE . '/class.cookie.php',
            'datetimeobj' => CORE . '/class.datetimeobj.php',
            'genericexceptionhandler' => CORE . '/class.errorhandler.php',
            'genericerrorhandler' => CORE . '/class.errorhandler.php',
            'log' => CORE . '/class.log.php',
            'session' => CORE . '/class.session.php',
            'symphony' => CORE . '/class.symphony.php',
            'symphonyerrorpageHandler' => CORE . '/class.symphony.php',
            'symphonyerrorpage' => CORE . '/class.symphony.php',
            'databaseexceptionhandler' => CORE . '/class.symphony.php',

            // Toolkit
            'page' => TOOLKIT . '/class.page.php',
            'textpage' => TOOLKIT . '/class.textpage.php',
            'jsonpage' => TOOLKIT . '/class.jsonpage.php',
            'xmlpage' => TOOLKIT . '/class.xmlpage.php',
            'ajaxpage' => TOOLKIT . '/class.ajaxpage.php',
            'htmlpage' => TOOLKIT . '/class.htmlpage.php',
            'xsltpage' => TOOLKIT . '/class.xsltpage.php',
            'administrationpage' => TOOLKIT . '/class.administrationpage.php',
            'frontendpage' => TOOLKIT . '/class.frontendpage.php',
            'resourcespage' => TOOLKIT . '/class.resourcespage.php',
            'mutex' => TOOLKIT . '/class.mutex.php',
            'xmlelement' => TOOLKIT . '/class.xmlelement.php',
            'widget' => TOOLKIT . '/class.widget.php',
            'general' => TOOLKIT . '/class.general.php',
            'lang' => TOOLKIT . '/class.lang.php',
            'cryptography' => TOOLKIT . '/class.cryptography.php',
            'xsrf' => TOOLKIT . '/class.xsrf.php',
            'profiler' => TOOLKIT . '/class.profiler.php',
            'author' => TOOLKIT . '/class.author.php',
            'email' => TOOLKIT . '/class.email.php',
            'mysql' => TOOLKIT . '/class.mysql.php',
            'gateway' => TOOLKIT . '/class.gateway.php',
            'alert' => TOOLKIT . '/class.alert.php',
            'extensionmanager' => TOOLKIT . '/class.extensionmanager.php',
            'pagemanager' => TOOLKIT . '/class.pagemanager.php',
            'authormanager' => TOOLKIT . '/class.authormanager.php',
            'emailgatewaymanager' => TOOLKIT . '/class.emailgatewaymanager.php',
            'entrymanager' => TOOLKIT . '/class.entrymanager.php',
            'fieldmanager' => TOOLKIT . '/class.fieldmanager.php',
            'sectionmanager' => TOOLKIT . '/class.sectionmanager.php',
            'textformattermanager' => TOOLKIT . '/class.textformattermanager.php',
            'datasourcemanager' => TOOLKIT . '/class.datasourcemanager.php',
            'eventmanager' => TOOLKIT . '/class.eventmanager.php',
            'resourcemanager' => TOOLKIT . '/class.resourcemanager.php',
            'xsltprocess' => TOOLKIT . '/class.xsltprocess.php',
            'event' => TOOLKIT . '/class.event.php',
            'datasource' => TOOLKIT . '/class.datasource.php',
            'emailgateway' => TOOLKIT . '/class.emailgateway.php',
            'emailhelper' => TOOLKIT . '/class.emailhelper.php',
            'smtp' => TOOLKIT . '/class.smtp.php',
            'author' => TOOLKIT . '/class.author.php',
            'entry' => TOOLKIT . '/class.entry.php',
            'field' => TOOLKIT . '/class.field.php',
            'textformatter' => TOOLKIT . '/class.textformatter.php',
            'textformattermanager' => TOOLKIT . '/class.textformattermanager.php',

            // Events
            'sectionevent' => TOOLKIT . '/events/class.event.section.php',
            'eventmessages' => TOOLKIT . '/events/class.event.section.php',

            // Datasources
            'authordatasource' => TOOLKIT . '/data-sources/class.datasource.author.php',
            'sectiondatasource' => TOOLKIT . '/data-sources/class.datasource.section.php',
            'staticdatasource' => TOOLKIT . '/data-sources/class.datasource.static.php',
            'dynamicxmldatasource' => TOOLKIT . '/data-sources/class.datasource.dynamic_xml.php',
            'navigationdatasource' => TOOLKIT . '/data-sources/class.datasource.navigation.php',

            // Cache
            'cachedatabase' => TOOLKIT . '/cache/cache.database.php',

            // Cryptography
            'md5' => TOOLKIT . '/cryptography/class.md5.php',
            'sha1' => TOOLKIT . '/cryptography/class.sha1.php',
            'pbkdf2' => TOOLKIT . '/cryptography/class.pbkdf2.php',

            // Content
            'sortable' => CONTENT . '/class.sortable.php',
            'contentajaxeventdocumentation' => CONTENT . '/content.ajaxeventdocumentation.php',

            // Fields
            'fielddate' => TOOLKIT . '/fields/field.date.php'
        );

        if(array_key_exists($class, $classes)) {
            require_once $classes[$class];
        }
    }

    spl_autoload_register('symphony_loader');
