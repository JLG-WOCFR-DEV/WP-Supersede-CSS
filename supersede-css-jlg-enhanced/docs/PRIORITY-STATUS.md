# Priorités — État d'avancement

## Fonctionnalités livrées
- Les demandes d'approbation disposent désormais d'un niveau de priorité normalisé (faible, normale, haute) enregistré côté serveur et exposé dans l'API `ssc/v1/approvals`.
- Le gestionnaire de tokens propose la sélection de priorité au moment de la soumission et affiche le badge associé sur chaque ligne pour repérer les urgences.
- Le Debug Center met en avant la priorité dans le tableau des approbations avec des badges colorés et relaie l'information dans le journal d'activité.
- Le CSS Performance Analyzer peut exporter ses recommandations en Markdown et JSON pour alimenter des tickets de suivi.

## Manques vs. roadmap Supersede CSS
- L'expérience de revue reste basique : la modal dédiée décrite dans la RFC (historique détaillé, commentaires contextuels) n'est pas encore implémentée et les décisions passent toujours par des dialogues natifs. ([Token Governance](./TOKEN-GOVERNANCE-AND-DEBUG.md))
- La priorisation ne déclenche pas encore de SLA ni de rappels automatiques (notifications, relances planifiées) alors que la feuille de route mentionne un workflow plus complet d'approbation et de publication. ([Token Governance](./TOKEN-GOVERNANCE-AND-DEBUG.md))
- La segmentation par environnements (draft/staging/production) et les exports différenciés en fonction du statut restent à livrer pour accompagner les équipes dans la promotion contrôlée des tokens. ([Token Governance](./TOKEN-GOVERNANCE-AND-DEBUG.md))

## Écart vs. applications concurrentes
- Aucune vue synthétique ne hiérarchise les demandes par SLA ou saturation de backlog, contrairement aux approches professionnelles évoquées dans l'analyse concurrentielle (ex. Activity Log avancé, commentaires inline). ([Competitive Analysis](./COMPETITIVE-ANALYSIS.md))
- L'absence de notifications collaboratives (mentions, intégrations Slack/Email) et de command palette limite encore la réactivité face aux priorités critiques identifiées comme différenciatrices chez Figma ou Webflow. ([Competitive Analysis](./COMPETITIVE-ANALYSIS.md))
- Le Device Lab et les workflows multi-supports (aperçu responsive, import d'URL externes) ne sont pas intégrés, ce qui freine la validation rapide des demandes prioritaires sur des surfaces variées. ([Competitive Analysis](./COMPETITIVE-ANALYSIS.md))
