#!/bin/bash
cd ..
find . -name \*dist -exec vi \-c :%s/\//g \-c :%s:\\s*$:: \-c ":set fileformat=unix" \-c :wq {} \;
find . -name \*php -exec vi \-c :%s/\//g \-c :%s:\\s*$:: \-c ":set fileformat=unix" \-c :wq {} \;
