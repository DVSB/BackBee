Contributing
============

First of all, **thank you** for contributing, **you are awesome**!

Here are a few rules to follow in order to ease code reviews, and discussions before
maintainers accept and merge your work.

You MUST follow the [PSR-1](http://www.php-fig.org/psr/1/) and
[PSR-2](http://www.php-fig.org/psr/2/). If you don't know about any of them, you
should really read the recommendations. Can't wait? Use the [PHP-CS-Fixer
tool](http://cs.sensiolabs.org/).

You MUST run the test suite.

You MUST write (or update) unit tests.

You SHOULD write documentation.

Please, write [commit messages that make
sense](http://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html),
and [rebase your branch](http://git-scm.com/book/en/Git-Branching-Rebasing)
before submitting your Pull Request.

One may ask you to [squash your
commits](http://gitready.com/advanced/2009/02/10/squashing-commits-with-rebase.html)
too. This is used to "clean" your Pull Request before merging it (we don't want
commits such as `fix tests`, `fix 2`, `fix 3`, etc.).

Also, while creating your Pull Request on GitHub, you MUST write a description
which gives the context and/or explains why you are creating it.

Worflow
-------

When you create a Pull Request ("PR"), if it's component related you can prefix it by the component name.
You can also use plus or minus to describe if you globaly add or remove something, and references it to an issue.

For instance, this is a PR valid label: ``[Rest] #42 + Updated & completed PageController tests``.

When you want to take an issue, create your PR prefixed by a [WIP] ("Work in progress") and add a "in progress" label.
This way, we know you are working on and we can give you some advices if needed.

When you have finished your PR, you can update the PR label to replace [WIP] by [RFR] ("Ready for review") prefix.

Consider your PR finished if:
* You have write a test with a new feature
* All tests pass
* The build is all green
* You (may) have introduced a little documentation

Deciders & mergers
------------------

BackBee have a core team who have rights on repositories.
* Only mergers can merge your work on master branch.
* If one of deciders give a ``:-1:`` on your suggest, the pull request won't be merged until he changes his mind.

Actual mergers are @eric-chau and @crouillon, actual deciders are @pasinter, @ndufreche and @fkroockmann.

All the core team is here to help you if you want to improve BackBee, we love contribution :) 
 

Thank you!

note: this ``CONTRIBUTING`` file is proudly inspired form [Hateoas' one](https://github.com/willdurand/Hateoas/blob/master/CONTRIBUTING.md)
