<?php
// FONCTION UTILES

if (!function_exists('chaineVersMot')) {
	function chaineVersMot($str): string
	{
		return str_replace('_', " d'", ucfirst($str));
	}
}

// Obtenir les différentes variables avec les infos nécessaires via des requêtes SQL
require dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/get_details_offre.php';

require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/horaire_controller.php';
$controllerHoraire = new HoraireController();
$horaires = $controllerHoraire->getHorairesOfOffre($id_offre);

foreach ($horaires as $jour => $horaire) {
	$horaires['ouverture'][$jour] = $horaire['ouverture'];
	$horaires['pause_debut'][$jour] = $horaire['pause_debut'];
	$horaires['pause_fin'][$jour] = $horaire['pause_fin'];
	$horaires['fermeture'][$jour] = $horaire['fermeture'];
}
$jour_semaine = date('l');
$jours_semaine_fr = [
	'Monday' => 'lundi',
	'Tuesday' => 'mardi',
	'Wednesday' => 'mercredi',
	'Thursday' => 'jeudi',
	'Friday' => 'vendredi',
	'Saturday' => 'samedi',
	'Sunday' => 'dimanche'
];

$jour_semaine = $jours_semaine_fr[$jour_semaine];
date_default_timezone_set('Europe/Paris');
$heure_actuelle = date('H:i');
$ouvert = false;

foreach ($horaires as $jour => $horaire) {
	if ($jour == $jour_semaine) {
		$ouverture = $horaire['ouverture'];
		$fermeture = $horaire['fermeture'];
		if ($ouverture !== null && $fermeture !== null) {
			if ($fermeture < $ouverture) {
				$fermeture_T = explode(':', $fermeture);
				$fermeture_T[0] = $fermeture_T[0] + 24;
				$fermeture_T = implode(':', $fermeture_T);
			} else {
				$fermeture_T = $fermeture;
			}
			if ($heure_actuelle >= $ouverture && $heure_actuelle <= $fermeture_T) {
				if ($horaire['pause_debut'] !== null && $horaire['pause_fin'] !== null) {
					$pause_debut = $horaire['pause_debut'];
					$pause_fin = $horaire['pause_fin'];
					if ($heure_actuelle >= $pause_debut && $heure_actuelle <= $pause_fin) {
						$ouvert = false;
					} else {
						if ($heure_actuelle >= $ouverture && $heure_actuelle <= $fermeture_T) {
							$ouvert = true;
						}
					}
				} else {
					$ouvert = true;
				}
			}
		}
	}
}

if ($mode_carte == 'membre') {
	// !!! CARD COMPONENT MEMBER !!!
	// Composant dynamique (généré avec les données en php)
	// Impossible d'en faire un composant pur (statique), donc écrit en HTML pur (copier la forme dans le php)
	?>
	<a class="card <?php if ($option) {
		echo "active roundex-lg";
	} ?> " href='/scripts/go_to_details.php?id_offre=<?php echo $id_offre ?>' <?php echo ($ouvert) ? "title='Ouvert'" : "title='Fermé'"; ?>>

		<!-- CARTE VERSION TÉLÉPHONE -->
		<div class='md:hidden relative bg-base100 roundex-xl flex flex-col'>
			<!-- En-tête -->
			<div
				class='en-tete absolute top-0 w-72 max-w-full bg-blur/75 backdrop-blur left-1/2 -translate-x-1/2 roundex-b-lg'>
				<h3 class='text-xl text-center font-bolx'>
					<?php echo $titre_offre; ?>
				</h3>
				<div class='flex w-full justify-between px-2'>
					<p class='text-small'><?php echo $pro['nom_pro'] ?></p>
					<p class='categorie text-small'><?php echo chaineVersMot($categorie_offre) ?></p>
				</div>
			</div>
			<!-- Image de fond -->
			<?php
			require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/image_controller.php';
			$controllerImage = new ImageController();
			$images = $controllerImage->getImagesOfOffre($id_offre);
			?>
			<img class="h-48 w-full roundex-t-lg object-cover" src='/public/images/<?php if ($images['carte']) {
				echo "offres/" . $images['carte'];
			} else {
				echo $categorie_offre . '.jpg';
			} ?>' alt="Image promotionnelle de l'offre">
			<!-- Infos principales -->
			<div class='infos flex items-center justify-around gap-2 px-2 grow'>
				<!-- Localisation -->
				<div class='localisation flex flex-col gap-2 flex-shrink-0 justify-center items-center min-w-16'>
					<i class='fa-solid fa-location-dot'></i>
					<p class='text-small'><?php
					if (strlen($ville) > 10) {
						echo substr($ville, 0, length: 7) . '...';
					} else {
						echo $ville;
					} ?></p>
					<p class='text-small'><?php echo $code_postal ?></p>
				</div>
				<hr class='h-20 border-black border'>
				<!-- Description avec les tags-->
				<div class='description py-2 flex flex-col gap-2 justify-center self-stretch'>
					<div class='p-1 roundex-lg bg-secondary self-center w-full'>
						<?php
						if ($categorie_offre != 'restauration') {
							require_once dirname(path: $_SERVER['DOCUMENT_ROOT']) . '/controller/tag_offre_controller.php';
							$controllerTagOffre = new TagOffreController();
							$tags_offre = $controllerTagOffre->getTagsByIdOffre($id_offre);

							require_once dirname(path: $_SERVER['DOCUMENT_ROOT']) . '/controller/tag_controller.php';
							$controllerTag = new TagController();
							$tagsAffiche = "";
							$tagsListe = [];
							foreach ($tags_offre as $tag) {
								array_push($tagsListe, $controllerTag->getInfosTag($tag['id_tag']));
							}
							foreach ($tagsListe as $tag) {
								$tagsAffiche .= $tag['nom'] . ', ';
							}

							$tagsAffiche = rtrim($tagsAffiche, ', ');
							if ($tags_offre) {
								?>
								<div class="p-1 roundex-lg bg-secondary self-center w-full">
									<?php
									echo ("<p class='tags text-white text-center overflow-ellipsis line-clamp-1'>$tagsAffiche</p>");
									?>
								</div>
								<?php
							} else {
								?>
								<div class="p-1 roundex-lg bg-secondary self-center w-full">
									<?php
									echo ("<p class='tags text-white text-center overflow-ellipsis line-clamp-1'>Aucun tag à afficher</p>");
									?>
								</div>
								<?php
							}
						} else {
							require_once dirname(path: $_SERVER['DOCUMENT_ROOT']) . '/controller/tag_restaurant_restauration_controller.php';
							$controllerTagRestRestauOffre = new tagRestaurantRestaurationController();
							$tags_offre = $controllerTagRestRestauOffre->getTagsByIdOffre($id_offre);

							require_once dirname(path: $_SERVER['DOCUMENT_ROOT']) . '/controller/tag_restaurant_controller.php';
							$controllerTagRest = new TagRestaurantController();
							$tagsAffiche = "";
							foreach ($tags_offre as $tag) {
								$tagsListe[] = $controllerTagRest->getInfosTagRestaurant($tag['id_tag_restaurant']);
							}
							foreach ($tagsListe as $tag) {
								$tagsAffiche .= $tag[0]['nom'] . ', ';
							}

							$tagsAffiche = rtrim($tagsAffiche, ', ');
							if ($tags_offre) {
								?>
								<div class="p-1 roundex-lg bg-secondary self-center w-full">
									<?php
									echo ("<p class='tags text-white text-center overflow-ellipsis line-clamp-1'>$tagsAffiche</p>");
									?>
								</div>
								<?php
							} else {
								?>
								<div class="p-1 roundex-lg bg-secondary self-center w-full">
									<?php
									echo ("<p class='tags text-white text-center overflow-ellipsis line-clamp-1'>Aucun tag à afficher</p>");
									?>
								</div>
								<?php
							}
						}
						?>
					</div>
					<p class='overflow-hidden line-clamp-2 text-small'>
						<?php echo $resume ?>
					</p>
				</div>
				<hr class='h-20 border-black border'>
				<!-- Notation et Prix -->
				<div class='flex flex-col gap-2 justify-center items-center min-w-16'>
					<?php
					// Moyenne des notes quand il y en a une
					if ($moyenne) {
						$n = $moyenne;
						?>
						<div class="note flex gap-1 flex-wrap" title="<?php echo $moyenne; ?>">
							<?php for ($i = 0; $i < 5; $i++) {
								if ($n > 1) {
									?>
									<img class="w-2" src="/public/icones/oeuf_plein.svg" alt="1 point de note">
									<?php
								} else if ($n > 0) {
									?>
										<img class="w-2" src="/public/icones/oeuf_moitie.svg" alt="0.5 point de note">
									<?php
								} else {
									?>
										<img class="w-2" src="/public/icones/oeuf_vide.svg" alt="0 point de note">
									<?php
								}
								$n--;
							}
							?>
							<!-- <p class='text-small italic flex items-center'>(<?php echo $nb_avis ?>)</p> -->
						</div>
						<?php
					}
					?>
					<p class='prix text-small'
						title='<?php echo (chaineVersMot($categorie_offre) !== 'Restauration') ? "Fourchette des prix : Min " . $tarif_min . ", Max " . $tarif_max : "Gamme des prix" ?>'>
						<?php echo $prix_a_afficher ?>
					</p>
				</div>
			</div>
		</div>

		<!-- CARTE VERSION TABLETTE -->
		<div class='md:block hidden relative bg-base100 roundex-lg'>
			<div class="flex flex-row">
				<!-- Partie gauche -->
				<div class='gauche grow relative shrink-0 basis-1/2 h-[280px] overflow-hidden'>
					<!-- Image de fond -->
					<?php
					require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/image_controller.php';
					$controllerImage = new ImageController();
					$images = $controllerImage->getImagesOfOffre($id_offre);
					?>
					<img class='roundex-l-lg w-full h-full object-cover object-center' src='/public/images/<?php if ($images['carte']) {
						echo "offres/" . $images['carte'];
					} else {
						echo $categorie_offre . '.jpg';
					} ?>' alt="Image promotionnelle de l'offre">
				</div>
				<!-- Partie droite (infos principales) -->
				<div class='infos flex flex-col basis-1/2 p-3 justify-between relative'>
					<!-- En tête avec titre -->
					<div class='en-tete relative top-0 max-w-full roundex-lg'>
						<div class="flex w-full">
							<h3 class='text-xl font-bolx grow'>
								<?php echo $titre_offre ?>
							</h3>
							<?php
							// Moyenne des notes quand il y en a une
							if ($moyenne) {
								$n = $moyenne;
								?>
								<div class="flex gap-1">
									<div class="note flex gap-1 shrink-0" title="<?php echo $moyenne; ?>">
										<?php for ($i = 0; $i < 5; $i++) {
											if ($n > 1) {
												?>
												<img class="w-3" src="/public/icones/oeuf_plein.svg" alt="1 point de note">
												<?php
											} else if ($n > 0) {
												?>
													<img class="w-3" src="/public/icones/oeuf_moitie.svg" alt="0.5 point de note">
												<?php
											} else {
												?>
													<img class="w-3" src="/public/icones/oeuf_vide.svg" alt="0 point de note">
												<?php
											}
											$n--;
										}
										?>
									</div>
									<p class='text-small italic flex items-center'>(<?php echo $nb_avis ?>)</p>
								</div>
								<?php
							}
							?>
						</div>
						<div class='flex'>
							<p class='text-small'><?php echo $pro['nom_pro'] ?></p>
							<p class='categorie text-small tablette'><?php echo ', ' . chaineVersMot($categorie_offre); ?>
							</p>
						</div>
					</div>

					<!-- Description + tags -->
					<div class='description py-2 flex flex-col gap-2 self-stretch grow'>
						<div class='p-1 roundex-lg bg-secondary self-center w-full'>
							<p class='text-white text-center'>
								<?php
								if ($categorie_offre != 'restauration') {
									require_once dirname(path: $_SERVER['DOCUMENT_ROOT']) . '/controller/tag_offre_controller.php';
									$controllerTagOffre = new TagOffreController();
									$tags_offre = $controllerTagOffre->getTagsByIdOffre($id_offre);

									require_once dirname(path: $_SERVER['DOCUMENT_ROOT']) . '/controller/tag_controller.php';
									$controllerTag = new TagController();
									$tagsAffiche = "";
									$tagsListe = [];
									foreach ($tags_offre as $tag) {
										array_push($tagsListe, $controllerTag->getInfosTag($tag['id_tag']));
									}
									foreach ($tagsListe as $tag) {
										$tagsAffiche .= $tag['nom'] . ', ';
									}

									$tagsAffiche = rtrim($tagsAffiche, ', ');
									if ($tags_offre) {
										?>
									<div class="p-1 roundex-lg bg-secondary self-center w-full">
										<?php
										echo ("<p class='tags text-white text-center overflow-ellipsis line-clamp-1'>$tagsAffiche</p>");
										?>
									</div>
									<?php
									} else {
										?>
									<div class="p-1 roundex-lg bg-secondary self-center w-full">
										<?php
										echo ("<p class='tags text-white text-center overflow-ellipsis line-clamp-1'>Aucun tag à afficher</p>");
										?>
									</div>
									<?php
									}
								} else {
									require_once dirname(path: $_SERVER['DOCUMENT_ROOT']) . '/controller/tag_restaurant_restauration_controller.php';
									$controllerTagRestRestauOffre = new tagRestaurantRestaurationController();
									$tags_offre = $controllerTagRestRestauOffre->getTagsByIdOffre($id_offre);

									require_once dirname(path: $_SERVER['DOCUMENT_ROOT']) . '/controller/tag_restaurant_controller.php';
									$controllerTagRest = new TagRestaurantController();
									$tagsAffiche = "";
									$tagsListe = [];
									foreach ($tags_offre as $tag) {
										$tagsListe[] = $controllerTagRest->getInfosTagRestaurant($tag['id_tag_restaurant']);
									}
									foreach ($tagsListe as $tag) {
										$tagsAffiche .= $tag[0]['nom'] . ', ';
									}

									$tagsAffiche = rtrim($tagsAffiche, ', ');
									if ($tags_offre) {
										?>
									<div class="p-1 roundex-lg bg-secondary self-center w-full">
										<?php
										echo ("<p class='tags text-white text-center overflow-ellipsis line-clamp-1'>$tagsAffiche</p>");
										?>
									</div>
									<?php
									} else {
										?>
									<div class="p-1 roundex-lg bg-secondary self-center w-full">
										<?php
										echo ("<p class='tags text-white text-center overflow-ellipsis line-clamp-1'>Aucun tag à afficher</p>");
										?>
									</div>
									<?php
									}
								}
								?>
							</p>
						</div>
						<p class='overflow-hidden line-clamp-5 text-small'>
							<?php echo $resume ?>
						</p>
					</div>
					<!-- A droite, en bas -->
					<div class='self-stretch flex flex-col gap-2'>
						<hr class='border-black w-full'>
						<div class='flex justify-around self-stretch'>
							<!-- Localisation -->
							<div class='localisation flex gap-2 flex-shrink-0 justify-center items-center'>
								<i class='fa-solid fa-location-dot'></i>
								<p class='text-small'><?php echo $ville ?></p>
								<p class='text-small'><?php echo $code_postal ?></p>
							</div>
							<!-- Notation et Prix -->
							<div class='flex flex-col flex-shrink-0 gap-2 justify-center items-center'>
								<p class='prix text-small'
									title='<?php echo (chaineVersMot($categorie_offre) !== 'Restauration') ? "Fourchette des prix : Min " . $tarif_min . ", Max " . $tarif_max : "Gamme des prix" ?>'>
									<?php echo $prix_a_afficher ?>
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</a>
	<?php
} else {
	// !!! CARD COMPONENT PRO !!!
	// Composant dynamique (généré avec les données en php)
	// Impossible d'en faire un composant pur (statique), donc écrit en HTML pur (copier la forme dans le php)
	?>
	<div class="card <?php if ($option)
		echo 'active' ?> relative max-w-[1280px] bg-base100 roundex-lg flex" <?php echo ($ouvert) ? "title='Ouvert'" : "title='Fermé'"; ?>>

		<!-- PARTIE DE GAUCHE, image-->
		<div class="gauche relative shrink-0 basis-1/2 h-[370px] overflow-hidden">
			<a href='/scripts/go_to_details_pro.php?id_offre=<?php echo $id_offre ?>'>
				<?php
				require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/image_controller.php';
				$controllerImage = new ImageController();
				$images = $controllerImage->getImagesOfOffre($id_offre);
				?>
				<img class="roundex-l-lg w-full h-full object-cover object-center" src='/public/images/<?php if ($images['carte']) {
					echo "offres/" . $images['carte'];
				} else {
					echo $categorie_offre . '.jpg';
				} ?>' alt="Image promotionnelle de l'offre" title="Consulter les détails">
			</a>
		</div>

		<!-- PARTIE DE DROITE (infos principales) -->
		<div class="infos relative flex flex-col items-center basis-1/2 self-stretch px-5 py-3 justify-between">

			<div class="w-full">
				<!-- A droite, en haut -->
				<div class="flex w-full items-center justify-between">

					<!-- Titre de l'offre -->
					<div>
						<h3 class="text-h2 font-bolx"><?php echo $titre_offre ?></h3>
						<div class="flex">
							<p class="text"><?php echo $pro['nom_pro'] ?></p>
							<p class="categorie text"><?php echo ', ' . chaineVersMot($categorie_offre) ?></p>
						</div>
					</div>

					<?php
					// Moyenne des notes quand il y en a une
					if ($moyenne) {
						$n = $moyenne;
						?>
						<div class="flex gap-1 self-end">
							<div class="note flex gap-1 shrink-0 m-1" title="<?php echo $moyenne; ?>">
								<?php for ($i = 0; $i < 5; $i++) {
									if ($n > 1) {
										?>
										<img class="w-3" src="/public/icones/oeuf_plein.svg" alt="1 point de note">
										<?php
									} else if ($n > 0) {
										?>
											<img class="w-3" src="/public/icones/oeuf_moitie.svg" alt="0.5 point de note">
										<?php
									} else {
										?>
											<img class="w-3" src="/public/icones/oeuf_vide.svg" alt="0 point de note">
										<?php
									}
									$n--;
								}
								?>
							</div>
							<p class='text-small italic flex items-center'>(<?php echo $nb_avis ?>)</p>
						</div>
						<?php
					}
					?>

					<!-- Manipulations sur l'offre -->
					<div class="flex gap-10 self-start items-center justify-center">
						<!-- en ligne ? -->
						<?php
						if ($est_en_ligne) {
							?>
							<a href="/scripts/toggle_ligne.php?id_offre=<?php echo $id_offre ?>"
								onclick="return confirm('Voulez-vous vraiment mettre <?php echo $titre_offre ?> hors ligne ?\nLa facturation s\'arrêtra à compter de demain.');"
								title=" [!!!] mettre hors-ligne">
								<svg class="toggle-wifi-offline p-1 roundex-lg border-rouge-logo hover:border-y-2 border-solid duration-100 hover:fill-[#EA4335]"
									width="55" height="40" viewBox="0 0 40 32" fill="#0a0035">
									<path
										d="M3.3876 12.6812C7.7001 8.54375 13.5501 6 20.0001 6C26.4501 6 32.3001 8.54375 36.6126 12.6812C37.4126 13.4437 38.6751 13.4187 39.4376 12.625C40.2001 11.8313 40.1751 10.5625 39.3814 9.8C34.3563 4.96875 27.5251 2 20.0001 2C12.4751 2 5.64385 4.96875 0.612605 9.79375C-0.181145 10.5625 -0.206145 11.825 0.556355 12.625C1.31885 13.425 2.5876 13.45 3.38135 12.6812H3.3876ZM20.0001 16C23.5501 16 26.7876 17.3188 29.2626 19.5C30.0939 20.2313 31.3564 20.15 32.0876 19.325C32.8189 18.5 32.7376 17.2312 31.9126 16.5C28.7376 13.7 24.5626 12 20.0001 12C15.4376 12 11.2626 13.7 8.09385 16.5C7.2626 17.2312 7.1876 18.4938 7.91885 19.325C8.6501 20.1562 9.9126 20.2313 10.7439 19.5C13.2126 17.3188 16.4501 16 20.0064 16H20.0001ZM24.0001 26C24.0001 24.9391 23.5787 23.9217 22.8285 23.1716C22.0784 22.4214 21.061 22 20.0001 22C18.9392 22 17.9218 22.4214 17.1717 23.1716C16.4215 23.9217 16.0001 24.9391 16.0001 26C16.0001 27.0609 16.4215 28.0783 17.1717 28.8284C17.9218 29.5786 18.9392 30 20.0001 30C21.061 30 22.0784 29.5786 22.8285 28.8284C23.5787 28.0783 24.0001 27.0609 24.0001 26Z" />
									<path class="invisible" d="M31 26.751L6 2.75098" stroke-width="3" stroke="#EA4335"
										stroke-linecap="round" />
								</svg>
							</a>
							<?php
						} else {
							?>
							<a <?php
							// Cas où aucun rib n'est rentré : ne pas pouvoir mettre en ligne
							if ($pro['data']['type'] == 'prive' && (!isset($pro['data']['id_rib']) || $pro['data']['id_rib'] == null)) {
								echo "onclick='return alert(\"Veuillez renseigner votre IBAN pour mettre {$titre_offre} en ligne\");'";
							} else {
								// Pouvoir mettre en ligne si tout est OK ou si public
								echo "href='/scripts/toggle_ligne.php?id_offre={$id_offre}' onclick='return confirm(\"Voulez-vous vraiment mettre {$titre_offre} en ligne ? N'hésitez pas à consulter de nouveau nos CGV\");'";
							}
							?> title="[!!!] mettre en ligne">
								<svg class="toggle-wifi-online p-1 roundex-lg hover:fill-[#00350D] border-secondary hover:border-y-2 border-solid duration-100"
									width="55" height="40" viewBox="0 0 40 32" fill="#EA4335">
									<path
										d="M3.3876 12.6812C7.7001 8.54375 13.5501 6 20.0001 6C26.4501 6 32.3001 8.54375 36.6126 12.6812C37.4126 13.4437 38.6751 13.4187 39.4376 12.625C40.2001 11.8313 40.1751 10.5625 39.3814 9.8C34.3563 4.96875 27.5251 2 20.0001 2C12.4751 2 5.64385 4.96875 0.612605 9.79375C-0.181145 10.5625 -0.206145 11.825 0.556355 12.625C1.31885 13.425 2.5876 13.45 3.38135 12.6812H3.3876ZM20.0001 16C23.5501 16 26.7876 17.3188 29.2626 19.5C30.0939 20.2313 31.3564 20.15 32.0876 19.325C32.8189 18.5 32.7376 17.2312 31.9126 16.5C28.7376 13.7 24.5626 12 20.0001 12C15.4376 12 11.2626 13.7 8.09385 16.5C7.2626 17.2312 7.1876 18.4938 7.91885 19.325C8.6501 20.1562 9.9126 20.2313 10.7439 19.5C13.2126 17.3188 16.4501 16 20.0064 16H20.0001ZM24.0001 26C24.0001 24.9391 23.5787 23.9217 22.8285 23.1716C22.0784 22.4214 21.061 22 20.0001 22C18.9392 22 17.9218 22.4214 17.1717 23.1716C16.4215 23.9217 16.0001 24.9391 16.0001 26C16.0001 27.0609 16.4215 28.0783 17.1717 28.8284C17.9218 29.5786 18.9392 30 20.0001 30C21.061 30 22.0784 29.5786 22.8285 28.8284C23.5787 28.0783 24.0001 27.0609 24.0001 26Z" />
									<path class="visible" d="M31 26.751L6 2.75098" stroke-width="3" stroke="#EA4335"
										stroke-linecap="round" />
								</svg>
							</a>
							<?php
						}
						?>
						<!-- modifier l'offre -->
						<a title="Modifier l'offre" class="hidden">
							<i class="fa-solid fa-gear text-secondary text-h1 hover:text-primary duration-100"></i>
						</a>
						<!-- détails de l'offre -->
						<a href="/scripts/go_to_details.php?id_offre=<?php echo $id_offre ?>" title="Voir l'offre">
							<i class="fa-solid fa-arrow-up-right-from-square text-h1 hover:text-primary duration-100"></i>
						</a>
					</div>
				</div>

				<!-- A droite, au milieu : description avec éventuels tags -->
				<div class=" description py-2 flex flex-col gap-2 w-full">
					<div class="flex justify-center relative">
						<div class="p-2 roundex-lg bg-secondary self-center w-full">
							<?php
							if ($categorie_offre != 'restauration') {
								require_once dirname(path: $_SERVER['DOCUMENT_ROOT']) . '/controller/tag_offre_controller.php';
								$controllerTagOffre = new TagOffreController();
								$tags_offre = $controllerTagOffre->getTagsByIdOffre($id_offre);

								require_once dirname(path: $_SERVER['DOCUMENT_ROOT']) . '/controller/tag_controller.php';
								$controllerTag = new TagController();
								$tagsAffiche = "";
								$tagsListe = [];
								foreach ($tags_offre as $tag) {
									array_push($tagsListe, $controllerTag->getInfosTag($tag['id_tag']));
								}
								foreach ($tagsListe as $tag) {
									$tagsAffiche .= $tag['nom'] . ', ';
								}

								$tagsAffiche = rtrim($tagsAffiche, ', ');
								if ($tags_offre) {
									?>
									<div class="p-1 roundex-lg bg-secondary self-center w-full">
										<?php
										echo ("<p class='tags text-white text-center overflow-ellipsis line-clamp-1'>$tagsAffiche</p>");
										?>
									</div>
									<?php
								} else {
									?>
									<div class="p-1 roundex-lg bg-secondary self-center w-full">
										<?php
										echo ("<p class='tags text-white text-center overflow-ellipsis line-clamp-1'>Aucun tag à afficher</p>");
										?>
									</div>
									<?php
								}
							} else {
								require_once dirname(path: $_SERVER['DOCUMENT_ROOT']) . '/controller/tag_restaurant_restauration_controller.php';
								$controllerTagRestRestauOffre = new tagRestaurantRestaurationController();
								$tags_offre = $controllerTagRestRestauOffre->getTagsByIdOffre($id_offre);

								require_once dirname(path: $_SERVER['DOCUMENT_ROOT']) . '/controller/tag_restaurant_controller.php';
								$controllerTagRest = new TagRestaurantController();
								$tagsAffiche = "";
								$tagsListe = [];
								foreach ($tags_offre as $tag) {
									$tagsListe[] = $controllerTagRest->getInfosTagRestaurant($tag['id_tag_restaurant']);
								}
								foreach ($tagsListe as $tag) {
									$tagsAffiche .= $tag[0]['nom'] . ', ';
								}

								$tagsAffiche = rtrim($tagsAffiche, ', ');
								if ($tags_offre) {
									?>
									<div class="p-1 roundex-lg bg-secondary self-center w-full">
										<?php
										echo ("<p class='tags text-white text-center overflow-ellipsis line-clamp-1'>$tagsAffiche</p>");
										?>
									</div>
									<?php
								} else {
									?>
									<div class="p-1 roundex-lg bg-secondary self-center w-full">
										<?php
										echo ("<p class='tags text-white text-center overflow-ellipsis line-clamp-1'>Aucun tag à afficher</p>");
										?>
									</div>
									<?php
								}
							}
							?>
						</div>
					</div>
					<p class="line-clamp-3">
						<?php echo $resume ?>
					</p>
				</div>
			</div>

			<!-- A droite, en bas -->
			<div class="self-stretch flex flex-col shrink-0 gap-2">
				<hr class="border-black w-full">
				<div class="flex justify-around self-stretch">
					<!-- Localisation -->
					<div title="Adresse de l'offre"
						class="localisation flex gap-2 flex-shrink-0 justify-center items-center">
						<i class="fa-solid fa-location-dot"></i>
						<p class="text-small"><?php echo $ville ?></p>
						<p class="text-small"><?php echo $code_postal ?></p>
					</div>

					<!-- Notation et Prix -->
					<div class="flex flex-col flex-shrink-0 gap-2 justify-center items-center">
						<p class="prix text-small"
							title="<?php echo (chaineVersMot($categorie_offre) !== 'Restauration') ? "Fourchette des prix : Min " . $tarif_min . ", Max " . $tarif_max : "Gamme des prix" ?>">
							<?php echo $prix_a_afficher ?>
						</p>
					</div>
				</div>

				<!-- Infos supplémentaires pour le pro -->
				<div class="border border-black p-1 roundex-lg flex justify-around">

					<!-- Avis & date de mise à jour -->
					<div class="flex flex-col items-center justify-around hidden">
						<div class="flex italic justify-start gap-4">
							<!-- Non vus -->
							<a title="Avis non consultés" class="hover:text-primary">
								<i class=" fa-solid fa-exclamation text-rouge-logo"></i>
								(0)
							</a>
							<!-- Non répondus -->
							<a title="Avis sans réponse" class="hover:text-primary">
								<i class="fa-solid fa-reply-all text-rouge-logo"></i>
								(0)
							</a>
							<!-- Blacklistés -->
							<a title="Avis blacklistés" class="hover:text-primary">
								<i class="fa-regular fa-eye-slash text-rouge-logo"></i>
								(0)
							</a>
						</div>
					</div>

					<!-- Dates de mise à jour -->
					<div class="flex justify-between text-small">
						<div class="flex items-center justify-arround">
							<i class="fa-solid fa-rotate text-xl"></i>
							<p class="italic">Modifiée le <?php echo $date_mise_a_jour ?></p>
						</div>
					</div>

					<!-- Type offre + options -->
					<div class="flex flex-col justify-between gap-2">
						<p class="type-offre text-center grow" title="type de l'offre">
							<?php echo 'Type : ' . $type_offre ?>
						</p>
						<div class="flex items-center gap-2">
							<i class="fa-solid fa-gears text-xl"></i>
							<div>
								<p>‘A la Une’ 10/09/24-17/09/24</p>
								<p>‘En relief' 10/09/24-17/09/24</p>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>

<?php } ?>