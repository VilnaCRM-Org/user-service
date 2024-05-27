const fs = require('fs');
const path = require('path');

class LocalizationGenerator {
  i18nPath;

  featurePath;

  jsonFileType;

  localizationFile;

  pathToWriteLocalization;

  pathToI18nFolder;

  pathToI18nFile;

  constructor(
    i18nPath = 'i18n',
    featurePath = 'src/features',
    jsonFileType = 'json',
    localizationFile = 'localization.json'
  ) {
    this.i18nPath = i18nPath;
    this.featurePath = featurePath;
    this.jsonFileType = jsonFileType;
    this.localizationFile = localizationFile;

    this.pathToWriteLocalization = `pages/${i18nPath}`;
    this.pathToI18nFolder = `${featurePath}/{folder}/${i18nPath}`;
    this.pathToI18nFile = `${featurePath}/{folder}/${i18nPath}/{file.name}`;
  }

  generateLocalizationFile() {
    const featureFolders = this.getFeatureFolders();

    if (!featureFolders.length) return;

    const localizationObj = featureFolders.reduce((acc, folder) => {
      const parsedLocalizationFromFolder = this.getLocalizationFromFolder(folder);

      return { ...acc, ...parsedLocalizationFromFolder };
    }, {});

    const filePath = path.join(
      path.dirname(__dirname),
      this.pathToWriteLocalization,
      this.localizationFile
    );
    const fileContent = JSON.stringify(localizationObj);

    this.writeLocalizationFile(fileContent, filePath);
  }

  getFeatureFolders() {
    const featureDirectories = fs.readdirSync(this.featurePath, {
      withFileTypes: true,
    });

    return featureDirectories
      .filter(directory => directory.isDirectory())
      .map(directory => directory.name);
  }

  getLocalizationFromFolder(folder) {
    const localizationFiles = fs.readdirSync(this.pathToI18nFolder.replace('{folder}', folder), {
      withFileTypes: true,
    });

    return localizationFiles.reduce((localizations, file) => {
      if (!file.isFile()) return localizations;

      const [language, fileType] = file.name.split('.');

      if (fileType !== this.jsonFileType) return localizations;

      const localizationContent = fs.readFileSync(
        this.pathToI18nFile.replace('{folder}', folder).replace('{file.name}', file.name),
        'utf8'
      );
      const parsedLocalization = JSON.parse(localizationContent);

      return {
        ...localizations,
        [language]: {
          translation: parsedLocalization,
        },
      };
    }, {});
  }

  // eslint-disable-next-line class-methods-use-this
  writeLocalizationFile(fileContent, filePath) {
    fs.writeFile(filePath, fileContent, err => {
      if (err) {
        throw new Error(err);
      }
    });
  }
}

module.exports = LocalizationGenerator;
