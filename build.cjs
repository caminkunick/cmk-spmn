const fs = require("fs");
const dirArchiver = require("dir-archiver");
const path = require("path");

// read plugin.json to json
const packageJson = JSON.parse(fs.readFileSync("./package.json", "utf8"));
const pluginJson = JSON.parse(fs.readFileSync("./plugin.json", "utf8"));

// pluginJson.last_updated to now YYYY-MM-DD HH:mm:ss
const now = new Date();
now.setHours(now.getHours() + 7);
const nowString = now.toISOString().replace(/T/, " ").replace(/\..+/, "");

// update plugin.json
pluginJson.version = packageJson.version;
pluginJson.last_updated = nowString;

// write plugin.json
fs.writeFileSync("./plugin.json", JSON.stringify(pluginJson, null, 2));

// read cmk-spmn.php to string
let cmkSpmn = fs.readFileSync("./cmk-spmn.php", "utf8");
// find version
const versionRegex = /Version:.*$/gm;
const version = `Version: ${packageJson.version}`;
// replace version
cmkSpmn = cmkSpmn.replace(versionRegex, version);
// find last_updated
const lastUpdatedRegex = /Last Updated:.*$/gm;
const lastUpdated = `Last Updated: ${nowString}`;
// replace last_updated
cmkSpmn = cmkSpmn.replace(lastUpdatedRegex, lastUpdated);
// write cmk-spmn.php
fs.writeFileSync("./cmk-spmn.php", cmkSpmn);

// bundle
const excludes = [
  ".DS_Store",
  ".stylelintrc.json",
  ".eslintrc",
  ".git",
  ".gitattributes",
  ".github",
  ".gitignore",
  "README.md",
  "composer.json",
  "composer.lock",
  "node_modules",
  "vendor",
  "package-lock.json",
  "package.json",
  ".travis.yml",
  "phpcs.xml.dist",
  "sass",
  "style.css.map",
  "yarn.lock",
  "plugin.json",
  "bun.lockb",
  "build.cjs",
];

const src = path.join(__dirname);
const dest = path.join(
  __dirname,
  `/../${packageJson.name}-${packageJson.version}.zip`
);
const archive = new dirArchiver(src, dest, true, excludes);
archive.createZip();
