<?php

class MatomoAnalyticsHooks {
        public static function onRegistration() {
                global $wgDBname, $wgMatomoAnalyticsID;
                $wgMatomoAnalyticsID = MatomoAnalytics::getSiteID( $wgDBname );
        }

	public static function matomoAnalyticsSchemaUpdates( DatabaseUpdater $updater ) {
		global $wgMatomoAnalyticsUseDB, $wgMatomoAnalyticsDatabase, $wgDBname;

		if ( $wgMatomoAnalyticsUseDB && $wgMatomoAnalyticsDatabase === $wgDBname ) {
			$updater->addExtensionTable( 'matomo',
				__DIR__ . '/../sql/matomo.sql' );
		}

		return true;
	}

	public static function wikiCreation( $dbname ) {
		MatomoAnalytics::addSite( $dbname );
	}

	public static function wikiDeletion( $dbw, $dbname ) {
		MatomoAnalytics::deleteSite( $dbname );
	}

	public static function wikiRename( $dbw, $old, $new ) {
		MatomoAnalytics::renameSite( $old, $new );
	}

	/**
	* Function to add Matomo JS to all MediaWiki pages
	*
	* Adds exclusion for users with 'noanalytics' userright
	*
	* @param Skin $skin Skin object
	* @param string &$text Output text.
	* @return bool
	*/
	public static function matomoScript( $skin, &$text = '' ) {
		global $wgMatomoAnalyticsServerURL, $wgUser, $wgDBname, $wgMatomoAnalyticsID, $wgMatomoAnalyticsGlobalID;

		if ( $wgUser->isAllowed( 'noanalytics' ) ) {
			$text .= '<!-- MatomoAnalytics: User right noanalytics is assigned. -->';
		} else {
			$id = strval( $wgMatomoAnalyticsID );
			$globalId = $wgMatomoAnalyticsGlobalID ? $wgMatomoAnalyticsGlobalID : 'false';
			$serverurl = $wgMatomoAnalyticsServerURL;
			$title = $skin->getRelevantTitle();
			$jstitle = Xml::encodeJsVar( $title->getPrefixedText() );
			$dbname = Xml::encodeJsVar( $wgDBname );
			$urltitle = $title->getPrefixedURL();
			$userType = $wgUser->isLoggedIn() ? "User" : "Anonymous";
			$text .= <<<SCRIPT
				<!-- Matomo -->
				<script type="text/javascript">
				var _paq = _paq || [];
				_paq.push(["trackPageView"]);
				_paq.push(["enableLinkTracking"]);
				(function() {
					var u = "{$serverurl}";
					var globalId = {$globalId};
					_paq.push(["setTrackerUrl", u + "piwik.php"]);
					_paq.push(['setDocumentTitle', {$dbname} + " - " + {$jstitle}]);
					_paq.push(["setSiteId", "{$id}"]);
					_paq.push(["setCustomVariable", 1, "userType", "{$userType}", "visit"]);
					if ( globalId ) {
					    _paq.push(['addTracker', u + "piwik.php", globalId]);
					}
					var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0]; g.type="text/javascript";
					g.defer=true; g.async=true; g.src=u+"piwik.js"; s.parentNode.insertBefore(g,s);
				})();
				</script>
				<!-- End Matomo Code -->
				<!-- Matomo Image Tracker -->
				<noscript><p><img src="{$serverurl}piwik.php?idsite={$id}&amp;rec=1&amp;action_name={$urltitle}" style="border:0;" alt="" /></p></noscript>
				<!-- End Matomo -->
SCRIPT;
		}

		return true;
	}
}

