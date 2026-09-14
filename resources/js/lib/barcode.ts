export type BarcodeBar = {
    x: number;
    width: number;
};

export type BarcodeGeometry = {
    bars: BarcodeBar[];
    format: 'CODE 128' | 'EAN-13';
    width: number;
};

const EAN_L = [
    '0001101',
    '0011001',
    '0010011',
    '0111101',
    '0100011',
    '0110001',
    '0101111',
    '0111011',
    '0110111',
    '0001011',
];
const EAN_G = [
    '0100111',
    '0110011',
    '0011011',
    '0100001',
    '0011101',
    '0111001',
    '0000101',
    '0010001',
    '0001001',
    '0010111',
];
const EAN_R = [
    '1110010',
    '1100110',
    '1101100',
    '1000010',
    '1011100',
    '1001110',
    '1010000',
    '1000100',
    '1001000',
    '1110100',
];
const EAN_PARITY = [
    'LLLLLL',
    'LLGLGG',
    'LLGGLG',
    'LLGGGL',
    'LGLLGG',
    'LGGLLG',
    'LGGGLL',
    'LGLGLG',
    'LGLGGL',
    'LGGLGL',
];

const CODE_128_PATTERNS = [
    '212222',
    '222122',
    '222221',
    '121223',
    '121322',
    '131222',
    '122213',
    '122312',
    '132212',
    '221213',
    '221312',
    '231212',
    '112232',
    '122132',
    '122231',
    '113222',
    '123122',
    '123221',
    '223211',
    '221132',
    '221231',
    '213212',
    '223112',
    '312131',
    '311222',
    '321122',
    '321221',
    '312212',
    '322112',
    '322211',
    '212123',
    '212321',
    '232121',
    '111323',
    '131123',
    '131321',
    '112313',
    '132113',
    '132311',
    '211313',
    '231113',
    '231311',
    '112133',
    '112331',
    '132131',
    '113123',
    '113321',
    '133121',
    '313121',
    '211331',
    '231131',
    '213113',
    '213311',
    '213131',
    '311123',
    '311321',
    '331121',
    '312113',
    '312311',
    '332111',
    '314111',
    '221411',
    '431111',
    '111224',
    '111422',
    '121124',
    '121421',
    '141122',
    '141221',
    '112214',
    '112412',
    '122114',
    '122411',
    '142112',
    '142211',
    '241211',
    '221114',
    '413111',
    '241112',
    '134111',
    '111242',
    '121142',
    '121241',
    '114212',
    '124112',
    '124211',
    '411212',
    '421112',
    '421211',
    '212141',
    '214121',
    '412121',
    '111143',
    '111341',
    '131141',
    '114113',
    '114311',
    '411113',
    '411311',
    '113141',
    '114131',
    '311141',
    '411131',
    '211412',
    '211214',
    '211232',
    '2331112',
];

function barsFromBits(bits: string, moduleWidth: number): BarcodeBar[] {
    const bars: BarcodeBar[] = [];

    for (let index = 0; index < bits.length; index++) {
        if (bits[index] !== '1') {
            continue;
        }

        const x = index * moduleWidth;
        const previous = bars.at(-1);

        if (previous && previous.x + previous.width === x) {
            previous.width += moduleWidth;
        } else {
            bars.push({ x, width: moduleWidth });
        }
    }

    return bars;
}

function ean13Geometry(value: string, moduleWidth: number): BarcodeGeometry {
    const digits = value.split('').map(Number);
    const parity = EAN_PARITY[digits[0]];
    let bits = '101';

    for (let index = 0; index < 6; index++) {
        bits +=
            parity[index] === 'L'
                ? EAN_L[digits[index + 1]]
                : EAN_G[digits[index + 1]];
    }

    bits += '01010';

    for (let index = 7; index < 13; index++) {
        bits += EAN_R[digits[index]];
    }

    bits += '101';

    return {
        bars: barsFromBits(bits, moduleWidth),
        format: 'EAN-13',
        width: bits.length * moduleWidth,
    };
}

function isValidEan13(value: string): boolean {
    if (!/^\d{13}$/.test(value)) {
        return false;
    }

    const digits = value.split('').map(Number);
    const sum = digits
        .slice(0, 12)
        .reduce(
            (total, digit, index) => total + digit * (index % 2 === 0 ? 1 : 3),
            0,
        );

    return (10 - (sum % 10)) % 10 === digits[12];
}

function code128Geometry(value: string, moduleWidth: number): BarcodeGeometry {
    const dataCodes = Array.from(
        value,
        (character) => character.charCodeAt(0) - 32,
    );
    const checksum =
        (104 +
            dataCodes.reduce(
                (total, code, index) => total + code * (index + 1),
                0,
            )) %
        103;
    const codes = [104, ...dataCodes, checksum, 106];
    const quietZone = 10;
    let module = quietZone;
    const bars: BarcodeBar[] = [];

    for (const code of codes) {
        const pattern = CODE_128_PATTERNS[code];

        for (let index = 0; index < pattern.length; index++) {
            const width = Number(pattern[index]);

            if (index % 2 === 0) {
                bars.push({
                    x: module * moduleWidth,
                    width: width * moduleWidth,
                });
            }

            module += width;
        }
    }

    return {
        bars,
        format: 'CODE 128',
        width: (module + quietZone) * moduleWidth,
    };
}

export function createBarcodeGeometry(
    value: string,
    moduleWidth = 2,
): BarcodeGeometry | null {
    if (isValidEan13(value)) {
        return ean13Geometry(value, moduleWidth);
    }

    if (/^[\x20-\x7E]+$/.test(value)) {
        return code128Geometry(value, moduleWidth);
    }

    return null;
}

export function barcodeFilename(name: string, sku: string): string {
    const safeName = `${name}-${sku}`
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-zA-Z0-9._-]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .toLowerCase();

    return `etiqueta-${safeName || 'producto'}.svg`;
}
