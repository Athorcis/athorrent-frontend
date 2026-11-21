# Changelog

All notable changes to this project will be documented in this file. See [commit-and-tag-version](https://github.com/absolute-version/commit-and-tag-version) for commit guidelines.

## [3.0.2](https://github.com/athorcis/athorrent-frontend/compare/v3.0.1...v3.0.2) (2026-11-21)

### Bug Fixes

* fix a doctrine deprecation ([90597ac](https://github.com/athorcis/athorrent-frontend/commit/90597acf4bd26f4423ff35c119950e365cf277c1))

### Performance Improvements

* enable cache for entrypoints.json ([86962ee](https://github.com/athorcis/athorrent-frontend/commit/86962ee2539d5a720e938396370259b40d57dbee))
* enable preload ([d885e41](https://github.com/athorcis/athorrent-frontend/commit/d885e417724f83955aea411b235941855fcc4ae4))

### Build System

* drop --pull always now that image are pinned ([9e93fb9](https://github.com/athorcis/athorrent-frontend/commit/9e93fb9fedb69d7d5f9dd168fa629889a6db6bc8))
* fix build ([e0b2bb2](https://github.com/athorcis/athorrent-frontend/commit/e0b2bb20d100debacf8e789a7bfd39798cc0044b))
* fix composer sha ([1ced122](https://github.com/athorcis/athorrent-frontend/commit/1ced122c7455bf2489461d8a56583f1a014de396))
* install unzip since we don't install composer through mlocati/php-extension-installer anymore ([a99d9c4](https://github.com/athorcis/athorrent-frontend/commit/a99d9c4b17b6ceafcb46b308c1fc2ff66b12313b))
* pin digest for github actions ([95cc59e](https://github.com/athorcis/athorrent-frontend/commit/95cc59eee5283a2d4c3943b1976feb897cb068e4))
* pin digest for node & yarn ([4334782](https://github.com/athorcis/athorrent-frontend/commit/43347824118a423633dbbe633b5dda84f19ba242))

### Chores

* **deps-dev:** bump eslint from 10.14.0 to 10.15.0 ([#101](https://github.com/athorcis/athorrent-frontend/issues/101)) ([debc40a](https://github.com/athorcis/athorrent-frontend/commit/debc40adcd3e128d2bf4104a6cd6ae95e31a0ff8))
* keep patch version in docker config ([3b47a23](https://github.com/athorcis/athorrent-frontend/commit/3b47a236ce3eaade5b65400d7b3f84691ce731f5))
* pinning all docker images ([517d1e4](https://github.com/athorcis/athorrent-frontend/commit/517d1e4a87b5e326dc7924c01e08ec365ce26d67))
* pinning also mlocati/php-extension-installer and composer ([5b543cd](https://github.com/athorcis/athorrent-frontend/commit/5b543cdb580ff3930aa2bfa5ea9d269dffb5b324))
* upgrade deps ([776b183](https://github.com/athorcis/athorrent-frontend/commit/776b18396595ab3040e9af7c6e67a1fc1e5bb10e))

## [3.0.1](https://github.com/athorcis/athorrent-frontend/compare/v3.0.0...v3.0.1) (2026-11-19)

### Security

* upgrade nginx to 1.31.9 ([302b9cb](https://github.com/athorcis/athorrent-frontend/commit/302b9cb74732277564dda7a106445bd52874b9b4))

### Build System

* don't create an anonymous volume for sessions in test ([3d23259](https://github.com/athorcis/athorrent-frontend/commit/3d232597dbdf2c112bc2d9cf1c438aa6c9ea70d2))

### Chores

* Update development dependencies: Babel 8.1.2, Symfony Encore 7.2.1, Cypress 16.3.0, Sass 1.108.2, and TypeScript ESLint 8.72.2 ([aa63775](https://github.com/athorcis/athorrent-frontend/commit/aa63775f1c551770112f22ad03e9ca048162fd0a), [612121c](https://github.com/athorcis/athorrent-frontend/commit/612121c9ce51755e7d0dfa87f592ce3856248c8c), [7ed78ac](https://github.com/athorcis/athorrent-frontend/commit/7ed78ac97ba2e172b53bdca04b38b7ec8c6df8e5), [97d4030](https://github.com/athorcis/athorrent-frontend/commit/97d4030fe0c622dd4f9aa2e76ac29129b98e006b), [9bf5337](https://github.com/athorcis/athorrent-frontend/commit/9bf53375f3ab29fd82e481afcd11a00bded4611d), [eee40e5](https://github.com/athorcis/athorrent-frontend/commit/eee40e5fdf6a7347f9f73e63d6e9eb8c4258f43e), [09dcaac](https://github.com/athorcis/athorrent-frontend/commit/09dcaac60f911bce85713dfba4b83701d47ff37e), [2796730](https://github.com/athorcis/athorrent-frontend/commit/2796730f38dfc1309ad15e9fea38d8227e16ce58))
* drop unused .dockerignore patterns ([43cf760](https://github.com/athorcis/athorrent-frontend/commit/43cf760413f87033e7e4c374dbd4c9123258e342))
* drop version from package.json ([ec3c760](https://github.com/athorcis/athorrent-frontend/commit/ec3c7609b55767a0c57af67e8509c20041c0e942))
* exclude CHANGELOG.md in .dockerignore ([9f5d508](https://github.com/athorcis/athorrent-frontend/commit/9f5d5085ac83796260cf5ee67a89c1fa55899043))
* switch from Volta to mise ([65fb5a3](https://github.com/athorcis/athorrent-frontend/commit/65fb5a36f45500e2b6767469df2574d49b8cc102))
* update commit-and-tag-version config ([217a25e](https://github.com/athorcis/athorrent-frontend/commit/217a25eb54fde42ffda9202e23d40512de578b96))
* Upgrade Composer, Node.js, PHP extension installer, and project dependencies ([09e1637](https://github.com/athorcis/athorrent-frontend/commit/09e16376c1f6be175347b96db20d1e30b35a55b9))

## [3.0.0](https://github.com/athorcis/athorrent-frontend/compare/v3.0.0...v2.0.1) (2026-11-11)
