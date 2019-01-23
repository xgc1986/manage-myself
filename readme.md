## Configuration

### envars
BITBUCKET_EMAIL=xxxx@yyyy.com
BITBUCKET_PASSWORD=********

## Usage
bin/console g:p:l

## Legend
Title -> **green** another's PR, **blue** you PR, red **Needs to bring master**, and in parenthesis will say the number of conflicts
Author -> **red** have comments, **yellow** have commited, but still have comments
Last -> **red** this PR is old and needs to be fixed asap
Tiramisu -> There are a lot of files to review
Reviewers -> **underline** has approved the PR, **RED** has to participate, **red** have been a commit and therefore has to review again, **yellow** somebody has replied to your comment

## Stuff
If you reply a comment with **Fixed** the chain of comments will be considered resolved, another option is delete this chain of comments
