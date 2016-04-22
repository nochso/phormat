# Contributing

Contributions of any kind are welcome! You..

1. [Fork this project](https://github.com/nochso/phormat/fork). If you're new to this,
   [start here](https://help.github.com/articles/fork-a-repo/).
2. Improve it.
3. Submit a [pull request](https://help.github.com/articles/creating-a-pull-request)

If you'd like to add new features, please open an issue first. Remember the scope of this project.

Of course your pull requests will be considered even if you don't follow the guidelines below. However it will definitely speed up the process :)

## Code style

* Code should be formatted with phormat obviously.
* Try to avoid deeply nested control structures. Ideally each method should only have one level of indentation.
  This is not a hard rule, but it generally makes it easier to read and test.

## Be awesome

* Run PHPUnit to make sure tests pass: `vendor/bin/phpunit`
* Write new or update existings tests.
* Document your changes in the README.
* Keep your [fork up-to-date](https://help.github.com/articles/syncing-a-fork/) by rebasing your branches before submitting a pull request.

### Commit message template
Please try to use the following commit template. It's a nice way to write
[atomic commits](https://en.wikipedia.org/wiki/Atomic_commit#Atomic_commit_convention).

You can set up your local clone to use it automatically:

`git config commit.template <path to template>`

```
# <type>: (If applied, this commit will...) <subject> (Max 50 char)
# |<----  Using a Maximum Of 50 Characters  ---->|


# Explain why this change is being made
# |<----   Try To Limit Each Line to a Maximum Of 72 Characters   ---->|

# Provide links or keys to any relevant tickets, articles or other resources
# Example: Github issue #23

# --- COMMIT END ---
# Type can be
#    feat (new feature)
#    fix (bug fix)
#    docs (changes to documentation)
#    style (formatting, missing semi colons, etc; no code change)
#    refactor (refactoring production code)
#    test (adding missing tests, refactoring tests; no production code change)
#    chore (updating grunt tasks etc; no production code change)
# --------------------
# Remember to
#    Separate subject from body with a blank line
#    Limit the subject line to 50 characters
#    Capitalize the subject line
#    Do not end the subject line with a period
#    Use the imperative mood in the subject line
#    Wrap the body at 72 characters
#    Use the body to explain what and why vs. how
#    Can use multiple lines with "-" for bullet points in body
# --------------------
# For more information about this template, check out
# https://gist.github.com/adeekshith/cd4c95a064977cdc6c50
```

## Code of conduct
**Be excellent to each other!**

![Bill and Ted being excellent](http://i.imgur.com/EPFd81a.jpg)