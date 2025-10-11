# Pistes d'amélioration prioritaires

Ce document développe les initiatives proposées pour faire évoluer Supersede CSS JLG (Enhanced) vers une expérience studio complète. Chaque piste résume les objectifs, les bénéfices attendus et les chantiers techniques pressentis.

## Mode « starter site »
- **Objectif** : générer la structure CSS initiale (tokens, presets, grilles) d’un nouveau site via un assistant guidé en 4 à 6 étapes.
- **Approche** :
  - Wizards thématiques (blog, SaaS, portfolio) générant typographies, couleurs et composants de base.
  - Détection des plugins actifs pour suggérer des configurations adaptées (WooCommerce, LMS…).
  - Export final sous forme de preset partageable + snapshot de tokens pour industrialiser l’onboarding des nouveaux projets.
- **Livrables clés** : workflows UI, endpoints REST pour créer presets/tokens en cascade, tests E2E sur les scénarios standards.

## Assistant IA contextuel
- **Objectif** : proposer un panneau facultatif alimenté par l’API OpenAI pour assister la génération et la correction de CSS.
- **Approche** :
  - Suggestions en langage naturel converties en classes utilitaires prêtes à l’emploi.
  - Analyse automatique du CSS généré avec détection des anti-patterns (doublons, spécificité excessive) et corrections proposées en un clic.
  - Journalisation des prompts et réponses pour audit et amélioration continue.
- **Livrables clés** : module de configuration API, hooks de sécurité (quota, modération), tests de robustesse sur données sensibles.

## CSS Performance Analyzer ✅
- **Statut** : prototype fonctionnel capable d’identifier la taille livrée, les doublons et les sélecteurs inutilisés.
- **Approche suivante** :
  - ✅ Ajouter un mode « comparaison » entre deux snapshots de build pour suivre la dérive du poids CSS.
  - ✅ Exporter les recommandations en Markdown/JSON afin d’alimenter des tickets automatisés.
  - Brancher le module sur les widgets de monitoring (voir plus bas) pour une visualisation historique.
- **Livrables clés** : pipeline de mesure, stockage des rapports, alertes configurables.

## Marketplace de presets
- **Objectif** : permettre l’import/export direct de presets partagés par la communauté.
- **Approche** :
  - Galerie en ligne avec prévisualisations interactives (screenshots + CSS live).
  - Système de notation/commentaires, tags thématiques et filtres par usage.
  - Workflow d’approbation et validation de compatibilité (versions plugin, dépendances).
- **Livrables clés** : API publique/privée, interface d’import dans WordPress, gouvernance des contributions.

## Export Figma
- **Objectif** : synchroniser tokens et styles Supersede avec une bibliothèque de composants Figma.
- **Approche** :
  - Mapping automatique des tokens (couleurs, typographies, espacements) vers des styles Figma.
  - Génération de composants dynamiques (boutons, cartes, grilles) alignés sur les presets actifs.
  - Synchronisation bidirectionnelle optionnelle avec diff visuel.
- **Livrables clés** : connecteur OAuth, API Figma REST/GraphQL, documentation d’usage pour les designers.

## Mode hors ligne
- **Objectif** : garantir la continuité de travail en mobilité ou sur réseau instable.
- **Approche** :
  - Mise en cache des dépendances critiques (packages JS/CSS, tokens) et stockage dans IndexedDB/local storage.
  - File de synchronisation différée avec reprise automatique et gestion des conflits.
  - Indicateurs UI (badge hors ligne, compteur de diff) et notifications à la reconnexion.
- **Livrables clés** : service worker dédié, tests de résilience réseau, scénarios E2E hors ligne.

## Widgets de monitoring
- **Objectif** : offrir une visibilité continue sur la qualité front-end directement depuis WordPress.
- **Approche** :
  - Graphiques temps réel ou périodiques (taille CSS, temps de génération, erreurs de build) alimentés par les rapports du Performance Analyzer.
  - Tableaux de bord configurables par rôle (intégrateur, lead technique) avec alertes.
  - Intégration des indicateurs Core Web Vitals et recommandations d’optimisation.
- **Livrables clés** : composants React/JS pour le dashboard, API d’agrégation, historique persistant.

---

Ces pistes doivent être priorisées selon l’impact utilisateur et la complexité technique. Une roadmap semestrielle est recommandée pour coordonner les efforts entre design, développement et pilotage produit.
