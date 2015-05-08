# CHANGELOG for 1.0.x

This changelog references the relevant changes (bug and security fixes) done in 0.12 minor versions.

## 1.0.0-DEV (still in progress)

  * __BC #295 : Coding standard class naming convention refactor in Abstract / Interface / Exception
  * __BC #177 [ClassContent]__ Changed ``AContent::getParam`` and ``AContent::setParam`` API, we cannot anymore use them to set or get every parameters. To do so, use respectively ``AContent::setAllParams`` and ``AContent::getAllParams``.
  * __BC #177 [ClassContent]__ Renamed method of AbstractClassContent, from ``getDefaultParameters`` to ``getDefaultParams``.
  * __BC #188 [Bundle]__ Renamed ``AbstractBaseBundle`` to ``AbstractBundle`` and removed ABundle, every bundle must extends ``AbstractBundle`` to be compatible with BackBee REST API
  * __feature #178 [Bundle] bundle's service identifier pattern and bundle's config service identifier pattern has changed__ (pattern changed from ``bundle.commentbundle`` to ``bundle.comment``, bundle's config service identifier still accessible by appending ``.config`` to bundle service identifier, which result to `bundle.comment.config``)
