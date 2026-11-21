<?php
	if(!function_exists('render_vvz_error_page')) {
		function render_vvz_error_page ($status, $headline, $message, $hint = '', $show_request = false) {
			$status = (int)$status;

			if(!headers_sent()) {
				http_response_code($status);
				header('Content-Type: text/html; charset=utf-8');
				header('Cache-Control: no-store, no-cache, must-revalidate');
			}

			$script_name = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '/index.php';
			$base_path = rtrim(dirname($script_name), '/');
			if($base_path === '.' || $base_path === '\\') {
				$base_path = '';
			}
			$base_url = $base_path;

			$path = null;
			if($show_request && isset($_SERVER['REQUEST_URI'])) {
				$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
				if($path && strlen($path) > 120) {
					$path = substr($path, 0, 117).'...';
				}
			}

			$start_url = $base_url.'/startseite';
			$faq_url = $base_url.'/faq';
			$favicon_url = $base_url.'/favicon.ico';
			$retry_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $base_url.'/';

			$status_class = 'vvz-error-'.($status === 404 ? '404' : '500');
?>
<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="robots" content="noindex, nofollow" />
	<title><?php print $status; ?> &middot; <?php print htmlspecialchars($headline, ENT_QUOTES, 'UTF-8'); ?></title>
	<link rel="icon" href="<?php print htmlspecialchars($favicon_url, ENT_QUOTES, 'UTF-8'); ?>" type="image/x-icon" />
	<style>
		:root {
			color-scheme: dark;
		}

		* {
			box-sizing: border-box;
		}

		html,
		body {
			min-height: 100%;
			margin: 0;
		}

		body {
			background: #050d18;
			color: #e8f3fb;
			font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, Arial, sans-serif;
			overflow-x: hidden;
		}

		.vvz-error {
			--accent: #24c7c0;
			--accent-2: #006092;
			--accent-soft: rgba(36, 199, 192, 0.22);
			--accent-line: rgba(36, 199, 192, 0.44);
			min-height: 100vh;
			display: grid;
			place-items: center;
			position: relative;
			isolation: isolate;
			padding: clamp(18px, 4vw, 54px);
			background:
				radial-gradient(circle at 14% 12%, rgba(36, 199, 192, 0.16), transparent 36%),
				radial-gradient(circle at 88% 82%, rgba(0, 96, 146, 0.34), transparent 40%),
				linear-gradient(140deg, #050d18 0%, #071728 52%, #040b13 100%);
		}

		.vvz-error.vvz-error-500 {
			--accent: #ff6b6b;
			--accent-2: #c72c5a;
			--accent-soft: rgba(255, 107, 107, 0.2);
			--accent-line: rgba(255, 107, 107, 0.44);
			background:
				radial-gradient(circle at 16% 16%, rgba(255, 107, 107, 0.17), transparent 35%),
				radial-gradient(circle at 86% 82%, rgba(199, 44, 90, 0.3), transparent 42%),
				linear-gradient(140deg, #0d0709 0%, #1d0b13 52%, #09050b 100%);
		}

		.vvz-error::before {
			content: "";
			position: absolute;
			inset: 0;
			z-index: -3;
			background-image:
				linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
				linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
			background-size: 64px 64px;
			mask-image: radial-gradient(circle at center, black 20%, transparent 78%);
		}

		.aurora {
			position: absolute;
			z-index: -2;
			width: clamp(260px, 38vw, 620px);
			aspect-ratio: 1;
			border-radius: 50%;
			filter: blur(80px);
			opacity: 0.48;
			pointer-events: none;
			animation: auroraDrift 16s ease-in-out infinite alternate;
		}

		.aurora-one {
			top: -16%;
			right: -8%;
			background: var(--accent);
		}

		.aurora-two {
			bottom: -24%;
			left: -10%;
			background: var(--accent-2);
			animation-delay: -7s;
			animation-duration: 20s;
		}

		.error-card {
			width: min(1080px, 100%);
			position: relative;
			overflow: hidden;
			border: 1px solid rgba(255, 255, 255, 0.13);
			border-radius: clamp(24px, 4vw, 38px);
			background:
				linear-gradient(135deg, rgba(255, 255, 255, 0.09), rgba(255, 255, 255, 0.025)),
				rgba(8, 20, 34, 0.78);
			box-shadow:
				0 40px 120px rgba(0, 0, 0, 0.55),
				inset 0 1px 0 rgba(255, 255, 255, 0.14);
			backdrop-filter: blur(20px);
		}

		.error-card::after {
			content: "";
			position: absolute;
			inset: 0;
			pointer-events: none;
			background: linear-gradient(120deg, transparent 25%, rgba(255, 255, 255, 0.08) 48%, transparent 60%);
			transform: translateX(-100%);
			animation: sheen 7s ease-in-out infinite;
		}

		.error-inner {
			position: relative;
			z-index: 1;
			padding: clamp(26px, 4.5vw, 58px);
		}

		.error-badge {
			display: inline-flex;
			align-items: center;
			gap: 9px;
			padding: 7px 13px;
			border: 1px solid var(--accent-line);
			border-radius: 999px;
			background: var(--accent-soft);
			color: #f4feff;
			font-size: 12px;
			font-weight: 700;
			letter-spacing: 0.12em;
			text-transform: uppercase;
		}

		.error-badge-dot {
			width: 8px;
			height: 8px;
			border-radius: 50%;
			background: var(--accent);
			box-shadow: 0 0 0 0 var(--accent-soft);
			animation: pulseDot 2.2s ease-out infinite;
		}

		.error-layout {
			display: grid;
			grid-template-columns: minmax(0, 1.18fr) minmax(260px, 0.82fr);
			gap: clamp(26px, 5vw, 64px);
			align-items: center;
			margin-top: clamp(20px, 3vw, 34px);
		}

		.error-eyebrow {
			margin: 0 0 12px;
			color: var(--accent);
			font-size: 12px;
			font-weight: 800;
			letter-spacing: 0.18em;
			text-transform: uppercase;
		}

		h1 {
			margin: 0;
			max-width: 16ch;
			font-size: clamp(34px, 5.6vw, 70px);
			line-height: 0.98;
			letter-spacing: -0.045em;
			text-wrap: balance;
		}

		.error-message {
			max-width: 58ch;
			margin: clamp(18px, 2.4vw, 26px) 0 0;
			color: #b8cad8;
			font-size: clamp(15px, 1.5vw, 18px);
			line-height: 1.68;
		}

		.error-request {
			display: flex;
			gap: 10px;
			align-items: baseline;
			flex-wrap: wrap;
			max-width: 100%;
			margin: clamp(18px, 2.4vw, 26px) 0 0;
			padding: 11px 14px;
			border: 1px solid rgba(255, 255, 255, 0.1);
			border-radius: 12px;
			background: rgba(0, 0, 0, 0.22);
			color: #8fa6b8;
			font-size: 12px;
		}

		.error-request span {
			font-weight: 700;
			letter-spacing: 0.08em;
			text-transform: uppercase;
		}

		.error-request code {
			min-width: 0;
			overflow-wrap: anywhere;
			color: #e6f7ff;
			font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
		}

		.error-actions {
			display: flex;
			flex-wrap: wrap;
			gap: 12px;
			margin-top: clamp(24px, 3vw, 36px);
		}

		.error-button {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			min-height: 46px;
			padding: 0 20px;
			border: 1px solid transparent;
			border-radius: 12px;
			font-size: 14px;
			font-weight: 700;
			text-decoration: none;
			transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
		}

		.error-button:hover {
			transform: translateY(-2px);
		}

		.error-button-primary {
			background: linear-gradient(135deg, var(--accent), var(--accent-2));
			color: #fff;
			box-shadow: 0 12px 30px var(--accent-soft);
		}

		.error-button-primary:hover {
			box-shadow: 0 18px 40px var(--accent-soft);
		}

		.error-button-secondary {
			border-color: rgba(255, 255, 255, 0.18);
			background: rgba(255, 255, 255, 0.045);
			color: #e6f3fb;
		}

		.error-button-secondary:hover {
			background: rgba(255, 255, 255, 0.09);
		}

		.error-hint {
			max-width: 58ch;
			margin: 16px 0 0;
			color: #7f97a9;
			font-size: 12.5px;
			line-height: 1.6;
		}

		.error-visual {
			position: relative;
			min-height: clamp(230px, 32vw, 370px);
			display: grid;
			place-items: center;
		}

		.error-code {
			position: relative;
			z-index: 2;
			font-size: clamp(88px, 15vw, 190px);
			font-weight: 900;
			line-height: 0.76;
			letter-spacing: -0.09em;
			background: linear-gradient(135deg, #ffffff 6%, var(--accent) 48%, var(--accent-2) 100%);
			-webkit-background-clip: text;
			background-clip: text;
			color: transparent;
			filter: drop-shadow(0 20px 42px var(--accent-soft));
		}

		.error-ring {
			position: absolute;
			border: 1px solid var(--accent-line);
			border-radius: 50%;
			opacity: 0.62;
		}

		.error-ring-one {
			width: 74%;
			aspect-ratio: 1;
			animation: spin 24s linear infinite;
		}

		.error-ring-two {
			width: 98%;
			aspect-ratio: 1;
			border-style: dashed;
			opacity: 0.35;
			animation: spin 38s linear infinite reverse;
		}

		.error-ring-one::before,
		.error-ring-two::before {
			content: "";
			position: absolute;
			top: 50%;
			left: -5px;
			width: 10px;
			height: 10px;
			border-radius: 50%;
			background: var(--accent);
			box-shadow: 0 0 22px var(--accent);
		}

		.error-ring-two::before {
			top: 8%;
			left: 72%;
			background: var(--accent-2);
			box-shadow: 0 0 22px var(--accent-2);
		}

		.error-orb {
			position: absolute;
			width: 46%;
			aspect-ratio: 1;
			border-radius: 50%;
			background: radial-gradient(circle at 32% 28%, rgba(255, 255, 255, 0.28), var(--accent-soft) 38%, transparent 72%);
			filter: blur(4px);
			animation: floatOrb 8s ease-in-out infinite alternate;
		}

		.error-footer {
			display: flex;
			justify-content: space-between;
			gap: 16px;
			flex-wrap: wrap;
			margin-top: clamp(24px, 3vw, 38px);
			padding-top: 18px;
			border-top: 1px solid rgba(255, 255, 255, 0.09);
			color: #7690a3;
			font-size: 12px;
			letter-spacing: 0.04em;
		}

		@keyframes auroraDrift {
			from { transform: translate3d(-2%, -2%, 0) scale(1); }
			to { transform: translate3d(4%, 3%, 0) scale(1.12); }
		}

		@keyframes sheen {
			0%, 62% { transform: translateX(-120%); }
			100% { transform: translateX(120%); }
		}

		@keyframes pulseDot {
			0% { box-shadow: 0 0 0 0 var(--accent-soft); }
			70% { box-shadow: 0 0 0 10px transparent; }
			100% { box-shadow: 0 0 0 0 transparent; }
		}

		@keyframes spin {
			to { transform: rotate(360deg); }
		}

		@keyframes floatOrb {
			from { transform: translateY(-6px) scale(0.98); }
			to { transform: translateY(8px) scale(1.04); }
		}

		@media (max-width: 760px) {
			.error-layout {
				grid-template-columns: 1fr;
			}

			.error-visual {
				order: -1;
				min-height: 190px;
			}

			.error-code {
				font-size: clamp(92px, 26vw, 140px);
			}

			h1 {
				max-width: 100%;
			}
		}

		@media (prefers-reduced-motion: reduce) {
			*,
			*::before,
			*::after {
				animation-duration: 0.001ms !important;
				animation-iteration-count: 1 !important;
				scroll-behavior: auto !important;
			}
		}
	</style>
</head>
<body>
	<main class="vvz-error <?php print $status_class; ?>">
		<div class="aurora aurora-one"></div>
		<div class="aurora aurora-two"></div>
		<section class="error-card" role="main">
			<div class="error-inner">
				<div class="error-badge"><span class="error-badge-dot"></span>Fehler <?php print $status; ?></div>
				<div class="error-layout">
					<div class="error-copy">
						<p class="error-eyebrow">Vorlesungsverzeichnis</p>
						<h1><?php print htmlspecialchars($headline, ENT_QUOTES, 'UTF-8'); ?></h1>
						<p class="error-message"><?php print htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
<?php
						if($path) {
?>
							<div class="error-request"><span>Angefragt</span><code><?php print htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?></code></div>
<?php
						}
?>
						<div class="error-actions">
							<a class="error-button error-button-primary" href="<?php print htmlspecialchars($start_url, ENT_QUOTES, 'UTF-8'); ?>">Zur Startseite</a>
<?php
							if($status === 500) {
?>
								<a class="error-button error-button-secondary" href="<?php print htmlspecialchars($retry_url, ENT_QUOTES, 'UTF-8'); ?>">Erneut versuchen</a>
<?php
							} else {
?>
								<a class="error-button error-button-secondary" href="<?php print htmlspecialchars($faq_url, ENT_QUOTES, 'UTF-8'); ?>">Häufige Fragen</a>
<?php
							}
?>
						</div>
<?php
						if($hint) {
?>
							<p class="error-hint"><?php print htmlspecialchars($hint, ENT_QUOTES, 'UTF-8'); ?></p>
<?php
						}
?>
					</div>
					<div class="error-visual" aria-hidden="true">
						<div class="error-orb"></div>
						<div class="error-ring error-ring-one"></div>
						<div class="error-ring error-ring-two"></div>
						<div class="error-code"><?php print $status; ?></div>
					</div>
				</div>
				<div class="error-footer">
					<span>Vorlesungsverzeichnis</span>
					<span>Fehler <?php print $status; ?></span>
				</div>
			</div>
		</section>
	</main>
</body>
</html>
<?php
		}
	}
?>
