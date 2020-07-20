#!/bin/bash

read -p "Please Enter the full path of your Magento 1.9 installation : "  path
if [ ! -d "$path" ]; then
    echo "Source path: $path doesn't exist"
    exit 1
fi
echo ".... Copying file app/code/community/ to $path/app/code/community/"
cp -r ./app/code/community/* "$path/app/code/community/"
echo ".... Copying file app/design/frontend/base/default/layout to $path/app/design/frontend/base/default/layout"
cp -r ./app/design/frontend/base/default/layout/* "$path/app/design/frontend/base/default/layout/"

echo ".... Copying file app/design/frontend/base/default/template/ to $path/app/design/frontend/base/default/template/"
cp -r ./app/design/frontend/base/default/template/* "$path/app/design/frontend/base/default/template/"

echo ".... Copying file app/etc/modules/ to $path/app/etc/modules/"
cp -r ./app/etc/modules/* "$path/app/etc/modules/"

echo ".... Copying file js/ to $path/js/"
cp -r ./js/* "$path/js/"

echo ".... Copying file skin/frontend/base/default to $path/skin/frontend/base/default"
cp -r ./skin/frontend/base/default/* "$path/skin/frontend/base/default/"


echo "..... All done..."
