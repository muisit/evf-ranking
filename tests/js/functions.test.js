import { pad, format_currency, format_datetime, format_date, is_valid } from "../../components/functions.js";
import moment from 'moment';

test('pad',() => {
  expect(pad(1)).toBe("01");
  expect(pad(2)).toBe("02");
  expect(pad(3)).toBe("03");
  expect(pad(0)).toBe("00");
  expect(pad(10)).toBe("10");
  expect(pad(19)).toBe("19");
  expect(pad(89)).toBe("89");
  expect(pad(102)).toBe("102");
  expect(pad(-1)).toBe("0-1");
});

test('format_currency', () => {
  expect(format_currency(1.2)).toBe("1.20");
  expect(format_currency("1.2")).toBe("1.20");
  expect(format_currency(NaN)).toBe("NaN");
  expect(format_currency([])).toBe("NaN");
  expect(format_currency([1.2])).toBe("1.20");
  expect(format_currency(["1.2"])).toBe("1.20");
  expect(format_currency({})).toBe("NaN");
  expect(format_currency(0)).toBe("0.00");
  expect(format_currency(-10)).toBe("-10.00");
});

test('format_datetime', () => {
  expect(format_datetime("2021-01-12 12:34:56")).toBe("2021-01-12 12:34:56");
  expect(format_datetime("2021-01-12")).toBe("2021-01-12 00:00:00");
  expect(format_datetime("01-12-2021")).toBe("2021-01-12 00:00:00");
  expect(format_datetime("01/12/2021 12:34:56")).toBe("2021-01-12 12:34:56");
  expect(format_datetime("12/01/2021 12:34:56")).toBe("2021-12-01 12:34:56");
  expect(format_datetime(moment("1904-06-28 01:02:03"))).toBe("1904-06-28 01:02:03");
});

test('format_date', () => {
  expect(format_date("2021-01-12 12:34:56")).toBe("2021-01-12");
  expect(format_date("2021-01-12")).toBe("2021-01-12");
  expect(format_date("01-12-2021")).toBe("2021-01-12");
  expect(format_date("01/12/2021 12:34:56")).toBe("2021-01-12");
  expect(format_date("12/01/2021 12:34:56")).toBe("2021-12-01");
  expect(format_date(moment("1904-06-28 01:02:03"))).toBe("1904-06-28");
});


test('is_valid', () => {
    expect(is_valid(1)).toBe(true);
    expect(is_valid(-1)).toBe(false);
    expect(is_valid()).toBe(false);
    expect(is_valid('1')).toBe(true);
    expect(is_valid('-1')).toBe(false);
    expect(is_valid(null)).toBe(false);
    expect(is_valid(1.1)).toBe(true);
    expect(is_valid("1.1")).toBe(true);
    expect(is_valid("-1.2")).toBe(false);
    expect(is_valid(-10.211)).toBe(false);
    expect(is_valid({test:1})).toBe(false);
    expect(is_valid("aaa")).toBe(false);
    expect(is_valid(['a','b'])).toBe(false);
    expect(is_valid([1])).toBe(false);
  });

