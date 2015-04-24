#!/bin/bash

# Testing

  # echo $(pwd)
  # echo $( cd $( dirname "${BASH_SOURCE[0]}" ) && pwd )
  # exit

# Joomla Path

  JPATH="$1"
  if [ ! -d "$JPATH" ]; then
    if [ -d "../../public_html/administrator" ] && [ -d "../../public_html/components" ]; then
      JPATH="../../public_html/"
    else
      echo "Invalid Joomla Path";
      exit
    fi
  fi

  if [[ ! $JPATH =~ [\/$] ]]; then
    JPATH="$JPATH/"
  fi

# Repository Path

  RPATH="$2"
  if [ ! -d "$RPATH" ]; then
    if [ -d "./admin" ] && [ -d "./client" ]; then
      RPATH="./"
    else
      echo "Invalid Repo Path";
      exit
    fi
  fi

  if [[ ! $RPATH =~ [\/$] ]]; then
    RPATH="$RPATH/"
  fi

# Repository Name

  // REPONAME="com_member"
  REPONAME=$(basename `pwd`)

# Absolute Path

  ARPATH=$( cd $RPATH && pwd )/
  AJPATH=$( cd $JPATH && pwd )/

# Echo

  echo "Repository name:"
  echo "$REPONAME"
  echo ""

  echo "Repository path:"
  echo "$ARPATH"
  echo ""

  echo "Joomla path:"
  echo "$AJPATH"
  echo ""

  read -p "Press any key to continue... " -n1 -s
  echo

# link function

  find $AJPATH -lname "*$REPONAME*"

  read -p "Press confirm before removing the above links... " -n1 -s
  echo
	read -p "Confirm again... " -n1 -s
  echo

# link function

  find $AJPATH -lname "*$REPONAME*" -exec rm {} \;
