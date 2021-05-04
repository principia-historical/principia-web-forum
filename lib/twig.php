<?php

class PrincipiaForumExtension extends \Twig\Extension\AbstractExtension {
	public function getFunctions() {
		return [
			new \Twig\TwigFunction('timeunits', 'timeunits', ['is_safe' => ['html']]),
			new \Twig\TwigFunction('timeunits2', 'timeunits2', ['is_safe' => ['html']]),
			new \Twig\TwigFunction('timelink', 'timelink', ['is_safe' => ['html']]),

			new \Twig\TwigFunction('can_view_forum', 'can_view_forum'),

			new \Twig\TwigFunction('if_empty_query2', 'if_empty_query2', ['is_safe' => ['html']]),

		];
	}
	public function getFilters() {
		return [

		];
	}
}
