#!/bin/bash

xelatex front.tex && bibtex front && xelatex front.tex && xelatex front.pdf && xelatex front.pdf
xelatex front.tex && bibtex front && xelatex front.tex && xelatex front.pdf && xelatex front.pdf
